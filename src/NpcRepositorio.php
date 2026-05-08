<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Repositório de acesso aos NPCs.
 *
 * Toda operação usa Prepared Statements reais (ATTR_EMULATE_PREPARES = false)
 * para neutralizar tentativas de SQL Injection.
 */
final class NpcRepositorio
{
    public const ATITUDES = ['Amigavel', 'Neutro', 'Hostil'];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getConexao();
    }

    /**
     * Lista NPCs com filtros opcionais por atitude, localização e termo de busca.
     *
     * O termo "busca" é aplicado via LIKE em nome, ocupacao e historia (case-insensitive
     * pelo collation utf8mb4_unicode_ci). Caracteres curinga do SQL são escapados.
     *
     * @param array{atitude?: string|null, localizacao?: string|null, busca?: string|null} $filtros
     * @return array<int, array<string, mixed>>
     */
    public function listar(array $filtros = []): array
    {
        $sql    = 'SELECT id, campanha_id, nome, ocupacao, localizacao, atitude, foto_arquivo,
                          historia, criado_em, atualizado_em
                   FROM npcs';
        $where  = [];
        $params = [];

        $atitude = $filtros['atitude'] ?? null;
        if (is_string($atitude) && in_array($atitude, self::ATITUDES, true)) {
            $where[]            = 'atitude = :atitude';
            $params[':atitude'] = $atitude;
        }

        $localizacao = $filtros['localizacao'] ?? null;
        if (is_string($localizacao) && trim($localizacao) !== '') {
            $where[]                = 'localizacao = :localizacao';
            $params[':localizacao'] = trim($localizacao);
        }

        $busca = $filtros['busca'] ?? null;
        if (is_string($busca) && trim($busca) !== '') {
            $termo            = '%' . self::escaparLike(trim($busca)) . '%';
            $where[]          = '(nome LIKE :busca OR ocupacao LIKE :busca OR historia LIKE :busca)';
            $params[':busca'] = $termo;
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY nome ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Escapa os curingas de LIKE (% e _) e a barra invertida usada como ESCAPE padrão.
     */
    private static function escaparLike(string $valor): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
    }

    /**
     * Conta NPCs cadastrados (usado no Dashboard).
     */
    public function contar(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM npcs');
        return $stmt === false ? 0 : (int) $stmt->fetchColumn();
    }

    /**
     * Retorna a lista de localizações distintas já cadastradas (para popular o filtro).
     *
     * @return array<int, string>
     */
    public function localizacoesDistintas(): array
    {
        $stmt = $this->pdo->query(
            'SELECT DISTINCT localizacao FROM npcs ORDER BY localizacao ASC'
        );
        if ($stmt === false) {
            return [];
        }
        return array_map(static fn(array $r): string => (string) $r['localizacao'], $stmt->fetchAll());
    }

    /**
     * Busca um NPC pelo identificador.
     *
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, campanha_id, nome, ocupacao, localizacao, atitude, foto_arquivo,
                    historia, criado_em, atualizado_em
             FROM npcs
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $linha = $stmt->fetch();
        return $linha === false ? null : $linha;
    }

    /**
     * Insere um novo NPC e retorna o ID gerado.
     *
     * @param array{nome: string, ocupacao: string, localizacao: string, atitude: string, historia: string} $dados
     */
    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO npcs (campanha_id, nome, ocupacao, localizacao, atitude, foto_arquivo, historia)
             VALUES (:campanha_id, :nome, :ocupacao, :localizacao, :atitude, :foto_arquivo, :historia)'
        );
        $stmt->bindValue(':campanha_id', $dados['campanha_id'] ?? null,
                         isset($dados['campanha_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':nome',        $dados['nome']);
        $stmt->bindValue(':ocupacao',    $dados['ocupacao']);
        $stmt->bindValue(':localizacao', $dados['localizacao']);
        $stmt->bindValue(':atitude',     $dados['atitude']);
        $foto = $dados['foto_arquivo'] ?? null;
        $stmt->bindValue(':foto_arquivo', $foto, $foto === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':historia',    $dados['historia']);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualiza os campos editáveis de um NPC existente.
     *
     * Se a chave 'foto_arquivo' NÃO estiver presente em $dados, a coluna
     * é preservada — útil quando o usuário edita o NPC sem reenviar foto.
     */
    public function atualizar(int $id, array $dados): bool
    {
        $sets   = ['campanha_id = :campanha_id', 'nome = :nome', 'ocupacao = :ocupacao',
                   'localizacao = :localizacao', 'atitude = :atitude', 'historia = :historia'];
        if (array_key_exists('foto_arquivo', $dados)) {
            $sets[] = 'foto_arquivo = :foto_arquivo';
        }
        $sql = 'UPDATE npcs SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':campanha_id', $dados['campanha_id'] ?? null,
                         isset($dados['campanha_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':nome',        $dados['nome']);
        $stmt->bindValue(':ocupacao',    $dados['ocupacao']);
        $stmt->bindValue(':localizacao', $dados['localizacao']);
        $stmt->bindValue(':atitude',     $dados['atitude']);
        $stmt->bindValue(':historia',    $dados['historia']);
        if (array_key_exists('foto_arquivo', $dados)) {
            $foto = $dados['foto_arquivo'];
            $stmt->bindValue(':foto_arquivo', $foto, $foto === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }
        return $stmt->execute();
    }

    /**
     * Remove um NPC pelo ID.
     */
    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM npcs WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
