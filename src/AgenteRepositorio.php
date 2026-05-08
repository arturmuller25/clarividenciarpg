<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/PericiaCatalog.php';

/**
 * Repositório completo de Agentes (PJs).
 *
 * Inclui CRUD da entidade principal + sincronização das tabelas filhas
 * (perícias, ataques, inventário, rituais) numa única transação por
 * salvamento — garantindo que a ficha nunca fique parcialmente persistida.
 *
 * Estratégia para filhas:
 *   - Salvar = DELETE all WHERE agente_id + INSERT linhas novas.
 *   - Mais simples que diff/upsert e adequado para volumes pequenos
 *     (uma ficha tem dezenas de itens, não milhares).
 */
final class AgenteRepositorio
{
    public const CLASSES   = ['Combatente', 'Especialista', 'Ocultista'];
    public const ATRIBUTOS = ['forca', 'agilidade', 'intelecto', 'vigor', 'presenca'];
    public const ELEMENTOS = ['Sangue', 'Morte', 'Conhecimento', 'Energia'];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getConexao();
    }

    /* ============================================================
     * LEITURA
     * ============================================================ */

    public function listar(array $filtros = []): array
    {
        $sql = "SELECT a.id, a.campanha_id, a.nome, a.jogador, a.origem, a.classe, a.nex,
                       a.foto_arquivo, a.pv_atual, a.pv_maximo, a.san_atual, a.san_maximo,
                       a.pe_atual, a.pe_maximo, a.criado_em, a.atualizado_em,
                       c.nome AS campanha_nome
                FROM agentes a
                LEFT JOIN campanhas c ON c.id = a.campanha_id";
        $where  = [];
        $params = [];

        $campanhaId = $filtros['campanha_id'] ?? null;
        if (is_int($campanhaId) && $campanhaId > 0) {
            $where[]                = 'a.campanha_id = :campanha_id';
            $params[':campanha_id'] = $campanhaId;
        }

        $classe = $filtros['classe'] ?? null;
        if (is_string($classe) && in_array($classe, self::CLASSES, true)) {
            $where[]           = 'a.classe = :classe';
            $params[':classe'] = $classe;
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY a.nome ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM agentes WHERE id = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $linha = $stmt->fetch();
        return $linha === false ? null : $linha;
    }

    /**
     * Busca o agente + todas as filhas (perícias, ataques, inventário, rituais).
     * Retorna ['agente' => [...], 'pericias' => [...], 'ataques' => [...],
     *          'inventario' => [...], 'rituais' => [...]] ou null se não existir.
     *
     * @return array<string, mixed>|null
     */
    public function buscarFichaCompleta(int $id): ?array
    {
        $agente = $this->buscarPorId($id);
        if ($agente === null) return null;

        return [
            'agente'     => $agente,
            'pericias'   => $this->listarPericias($id),
            'ataques'    => $this->listarAtaques($id),
            'inventario' => $this->listarInventario($id),
            'rituais'    => $this->listarRituais($id),
        ];
    }

    public function listarPericias(int $agenteId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pericia, grau, bonus_extra
             FROM agente_pericias WHERE agente_id = :id ORDER BY pericia ASC'
        );
        $stmt->bindValue(':id', $agenteId, PDO::PARAM_INT);
        $stmt->execute();
        $linhas = $stmt->fetchAll();
        // indexar por nome da perícia para facilitar lookup na UI
        $map = [];
        foreach ($linhas as $l) {
            $map[(string) $l['pericia']] = $l;
        }
        return $map;
    }

    public function listarAtaques(int $agenteId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, atributo_base, bonus_arma, bonus_extra, dano, tipo_dano, descricao, ordem
             FROM agente_ataques WHERE agente_id = :id ORDER BY ordem ASC, id ASC'
        );
        $stmt->bindValue(':id', $agenteId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listarInventario(int $agenteId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, descricao, categoria, espacos, quantidade, equipado, ordem
             FROM agente_inventario WHERE agente_id = :id ORDER BY ordem ASC, id ASC'
        );
        $stmt->bindValue(':id', $agenteId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listarRituais(int $agenteId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, circulo, elemento, custo_pe, descricao, ordem
             FROM agente_rituais WHERE agente_id = :id ORDER BY circulo ASC, ordem ASC, id ASC'
        );
        $stmt->bindValue(':id', $agenteId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contar(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM agentes');
        return $stmt === false ? 0 : (int) $stmt->fetchColumn();
    }

    /* ============================================================
     * ESCRITA — agente principal + filhas em uma transação
     * ============================================================ */

    /**
     * Cria um agente novo + filhas. Retorna o ID gerado.
     */
    public function criar(array $ficha): int
    {
        $this->pdo->beginTransaction();
        try {
            $id = $this->inserirAgente($ficha['agente']);
            $this->salvarPericias($id, $ficha['pericias'] ?? []);
            $this->salvarAtaques($id, $ficha['ataques'] ?? []);
            $this->salvarInventario($id, $ficha['inventario'] ?? []);
            $this->salvarRituais($id, $ficha['rituais'] ?? []);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Atualiza um agente existente + reescreve as filhas.
     */
    public function atualizar(int $id, array $ficha): bool
    {
        $this->pdo->beginTransaction();
        try {
            $this->atualizarAgente($id, $ficha['agente']);
            $this->salvarPericias($id, $ficha['pericias'] ?? []);
            $this->salvarAtaques($id, $ficha['ataques'] ?? []);
            $this->salvarInventario($id, $ficha['inventario'] ?? []);
            $this->salvarRituais($id, $ficha['rituais'] ?? []);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Apenas atualiza o foto_arquivo (após upload).
     */
    public function atualizarFoto(int $id, ?string $arquivo): bool
    {
        $stmt = $this->pdo->prepare('UPDATE agentes SET foto_arquivo = :foto WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if ($arquivo === null) {
            $stmt->bindValue(':foto', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':foto', $arquivo);
        }
        return $stmt->execute();
    }

    public function excluir(int $id): bool
    {
        // ON DELETE CASCADE em FKs já remove filhas automaticamente
        $stmt = $this->pdo->prepare('DELETE FROM agentes WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /* ============================================================
     * ESCRITA — privadas
     * ============================================================ */

    private function inserirAgente(array $a): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO agentes
                (campanha_id, nome, jogador, origem, classe, nex, foto_arquivo,
                 pv_atual, pv_maximo, san_atual, san_maximo, pe_atual, pe_maximo,
                 forca, agilidade, intelecto, vigor, presenca,
                 pe_por_turno, deslocamento, defesa, resistencias, proficiencias,
                 aparencia, personalidade, historico, objetivos)
             VALUES
                (:campanha_id, :nome, :jogador, :origem, :classe, :nex, :foto_arquivo,
                 :pv_atual, :pv_maximo, :san_atual, :san_maximo, :pe_atual, :pe_maximo,
                 :forca, :agilidade, :intelecto, :vigor, :presenca,
                 :pe_por_turno, :deslocamento, :defesa, :resistencias, :proficiencias,
                 :aparencia, :personalidade, :historico, :objetivos)'
        );
        $this->bindAgente($stmt, $a);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    private function atualizarAgente(int $id, array $a): void
    {
        $sets = [
            'campanha_id = :campanha_id',
            'nome = :nome', 'jogador = :jogador', 'origem = :origem',
            'classe = :classe', 'nex = :nex',
            'pv_atual = :pv_atual', 'pv_maximo = :pv_maximo',
            'san_atual = :san_atual', 'san_maximo = :san_maximo',
            'pe_atual = :pe_atual', 'pe_maximo = :pe_maximo',
            'forca = :forca', 'agilidade = :agilidade', 'intelecto = :intelecto',
            'vigor = :vigor', 'presenca = :presenca',
            'pe_por_turno = :pe_por_turno', 'deslocamento = :deslocamento',
            'defesa = :defesa', 'resistencias = :resistencias',
            'proficiencias = :proficiencias',
            'aparencia = :aparencia', 'personalidade = :personalidade',
            'historico = :historico', 'objetivos = :objetivos',
        ];
        // foto_arquivo só atualiza se o array trouxer (preserva a foto atual senão)
        if (array_key_exists('foto_arquivo', $a)) {
            $sets[] = 'foto_arquivo = :foto_arquivo';
        }

        $sql = 'UPDATE agentes SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindAgente($stmt, $a);
        $stmt->execute();
    }

    private function bindAgente(PDOStatement $stmt, array $a): void
    {
        // campanha_id pode ser null
        if (isset($a['campanha_id']) && $a['campanha_id'] !== null) {
            $stmt->bindValue(':campanha_id', (int) $a['campanha_id'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':campanha_id', null, PDO::PARAM_NULL);
        }

        $stmt->bindValue(':nome',          (string) $a['nome']);
        $stmt->bindValue(':jogador',       $a['jogador'] ?? null);
        $stmt->bindValue(':origem',        $a['origem'] ?? null);
        $stmt->bindValue(':classe',        (string) $a['classe']);
        $stmt->bindValue(':nex',           (int) $a['nex'], PDO::PARAM_INT);

        if (array_key_exists('foto_arquivo', $a)) {
            if ($a['foto_arquivo'] === null) {
                $stmt->bindValue(':foto_arquivo', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':foto_arquivo', (string) $a['foto_arquivo']);
            }
        }

        $stmt->bindValue(':pv_atual',      (int) $a['pv_atual'],   PDO::PARAM_INT);
        $stmt->bindValue(':pv_maximo',     (int) $a['pv_maximo'],  PDO::PARAM_INT);
        $stmt->bindValue(':san_atual',     (int) $a['san_atual'],  PDO::PARAM_INT);
        $stmt->bindValue(':san_maximo',    (int) $a['san_maximo'], PDO::PARAM_INT);
        $stmt->bindValue(':pe_atual',      (int) $a['pe_atual'],   PDO::PARAM_INT);
        $stmt->bindValue(':pe_maximo',     (int) $a['pe_maximo'],  PDO::PARAM_INT);

        $stmt->bindValue(':forca',         (int) $a['forca'],      PDO::PARAM_INT);
        $stmt->bindValue(':agilidade',     (int) $a['agilidade'],  PDO::PARAM_INT);
        $stmt->bindValue(':intelecto',     (int) $a['intelecto'],  PDO::PARAM_INT);
        $stmt->bindValue(':vigor',         (int) $a['vigor'],      PDO::PARAM_INT);
        $stmt->bindValue(':presenca',      (int) $a['presenca'],   PDO::PARAM_INT);

        $stmt->bindValue(':pe_por_turno',  (int) $a['pe_por_turno'], PDO::PARAM_INT);
        $stmt->bindValue(':deslocamento',  (int) $a['deslocamento'], PDO::PARAM_INT);
        $stmt->bindValue(':defesa',        (int) $a['defesa'],       PDO::PARAM_INT);
        $stmt->bindValue(':resistencias',  $a['resistencias'] ?? null);
        $stmt->bindValue(':proficiencias', $a['proficiencias'] ?? null);

        $stmt->bindValue(':aparencia',     $a['aparencia'] ?? null);
        $stmt->bindValue(':personalidade', $a['personalidade'] ?? null);
        $stmt->bindValue(':historico',     $a['historico'] ?? null);
        $stmt->bindValue(':objetivos',     $a['objetivos'] ?? null);
    }

    private function salvarPericias(int $agenteId, array $pericias): void
    {
        $del = $this->pdo->prepare('DELETE FROM agente_pericias WHERE agente_id = :id');
        $del->bindValue(':id', $agenteId, PDO::PARAM_INT);
        $del->execute();

        if ($pericias === []) return;

        $ins = $this->pdo->prepare(
            'INSERT INTO agente_pericias (agente_id, pericia, grau, bonus_extra)
             VALUES (:agente_id, :pericia, :grau, :bonus_extra)'
        );
        foreach ($pericias as $nome => $info) {
            $grau  = (string) ($info['grau'] ?? 'Destreinado');
            $bonus = (int)    ($info['bonus_extra'] ?? 0);
            // Não persistir Destreinado com bonus 0 — economia de linhas
            if ($grau === 'Destreinado' && $bonus === 0) continue;
            if (!PericiaCatalog::existe((string) $nome) || !PericiaCatalog::grauValido($grau)) continue;

            $ins->bindValue(':agente_id',   $agenteId, PDO::PARAM_INT);
            $ins->bindValue(':pericia',     (string) $nome);
            $ins->bindValue(':grau',        $grau);
            $ins->bindValue(':bonus_extra', $bonus, PDO::PARAM_INT);
            $ins->execute();
        }
    }

    private function salvarAtaques(int $agenteId, array $ataques): void
    {
        $del = $this->pdo->prepare('DELETE FROM agente_ataques WHERE agente_id = :id');
        $del->bindValue(':id', $agenteId, PDO::PARAM_INT);
        $del->execute();

        if ($ataques === []) return;

        $ins = $this->pdo->prepare(
            'INSERT INTO agente_ataques
                (agente_id, nome, atributo_base, bonus_arma, bonus_extra, dano, tipo_dano, descricao, ordem)
             VALUES
                (:agente_id, :nome, :atributo_base, :bonus_arma, :bonus_extra, :dano, :tipo_dano, :descricao, :ordem)'
        );
        $ordem = 0;
        foreach ($ataques as $a) {
            $nome = trim((string) ($a['nome'] ?? ''));
            if ($nome === '') continue;  // ignora linhas vazias
            $atrib = (string) ($a['atributo_base'] ?? 'forca');
            if (!in_array($atrib, self::ATRIBUTOS, true)) $atrib = 'forca';

            $ins->bindValue(':agente_id',     $agenteId, PDO::PARAM_INT);
            $ins->bindValue(':nome',          $nome);
            $ins->bindValue(':atributo_base', $atrib);
            $ins->bindValue(':bonus_arma',    (int) ($a['bonus_arma']  ?? 0), PDO::PARAM_INT);
            $ins->bindValue(':bonus_extra',   (int) ($a['bonus_extra'] ?? 0), PDO::PARAM_INT);
            $ins->bindValue(':dano',          (string) ($a['dano'] ?? '1d4'));
            $ins->bindValue(':tipo_dano',     $a['tipo_dano'] ?? null);
            $ins->bindValue(':descricao',     $a['descricao'] ?? null);
            $ins->bindValue(':ordem',         $ordem++, PDO::PARAM_INT);
            $ins->execute();
        }
    }

    private function salvarInventario(int $agenteId, array $itens): void
    {
        $del = $this->pdo->prepare('DELETE FROM agente_inventario WHERE agente_id = :id');
        $del->bindValue(':id', $agenteId, PDO::PARAM_INT);
        $del->execute();

        if ($itens === []) return;

        $ins = $this->pdo->prepare(
            'INSERT INTO agente_inventario
                (agente_id, nome, descricao, categoria, espacos, quantidade, equipado, ordem)
             VALUES
                (:agente_id, :nome, :descricao, :categoria, :espacos, :quantidade, :equipado, :ordem)'
        );
        $ordem = 0;
        foreach ($itens as $it) {
            $nome = trim((string) ($it['nome'] ?? ''));
            if ($nome === '') continue;
            $ins->bindValue(':agente_id',  $agenteId, PDO::PARAM_INT);
            $ins->bindValue(':nome',       $nome);
            $ins->bindValue(':descricao',  $it['descricao'] ?? null);
            $ins->bindValue(':categoria',  $it['categoria'] ?? null);
            $ins->bindValue(':espacos',    (float) ($it['espacos'] ?? 1.0));
            $ins->bindValue(':quantidade', (int) ($it['quantidade'] ?? 1), PDO::PARAM_INT);
            $ins->bindValue(':equipado',   !empty($it['equipado']) ? 1 : 0, PDO::PARAM_INT);
            $ins->bindValue(':ordem',      $ordem++, PDO::PARAM_INT);
            $ins->execute();
        }
    }

    private function salvarRituais(int $agenteId, array $rituais): void
    {
        $del = $this->pdo->prepare('DELETE FROM agente_rituais WHERE agente_id = :id');
        $del->bindValue(':id', $agenteId, PDO::PARAM_INT);
        $del->execute();

        if ($rituais === []) return;

        $ins = $this->pdo->prepare(
            'INSERT INTO agente_rituais
                (agente_id, nome, circulo, elemento, custo_pe, descricao, ordem)
             VALUES
                (:agente_id, :nome, :circulo, :elemento, :custo_pe, :descricao, :ordem)'
        );
        $ordem = 0;
        foreach ($rituais as $r) {
            $nome = trim((string) ($r['nome'] ?? ''));
            if ($nome === '') continue;
            $elem = (string) ($r['elemento'] ?? 'Conhecimento');
            if (!in_array($elem, self::ELEMENTOS, true)) $elem = 'Conhecimento';
            $circ = (int) ($r['circulo'] ?? 1);
            if ($circ < 1 || $circ > 5) $circ = 1;

            $ins->bindValue(':agente_id', $agenteId, PDO::PARAM_INT);
            $ins->bindValue(':nome',      $nome);
            $ins->bindValue(':circulo',   $circ, PDO::PARAM_INT);
            $ins->bindValue(':elemento',  $elem);
            $ins->bindValue(':custo_pe',  (int) ($r['custo_pe'] ?? 1), PDO::PARAM_INT);
            $ins->bindValue(':descricao', $r['descricao'] ?? null);
            $ins->bindValue(':ordem',     $ordem++, PDO::PARAM_INT);
            $ins->execute();
        }
    }
}
