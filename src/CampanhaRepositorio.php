<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Repositório das campanhas. Toda operação usa Prepared Statements reais.
 *
 * Subqueries de contagem (total_agentes, total_npcs, total_criaturas) são
 * calculadas no momento da listagem para evitar tabela materializada.
 */
final class CampanhaRepositorio
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getConexao();
    }

    /**
     * Lista campanhas com contagens de entidades vinculadas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(): array
    {
        $stmt = $this->pdo->query(
            'SELECT c.id, c.nome, c.sistema, c.descricao, c.capa_arquivo,
                    c.criado_em, c.atualizado_em,
                    (SELECT COUNT(*) FROM agentes   a WHERE a.campanha_id = c.id) AS total_agentes,
                    (SELECT COUNT(*) FROM npcs      n WHERE n.campanha_id = c.id) AS total_npcs,
                    (SELECT COUNT(*) FROM criaturas k WHERE k.campanha_id = c.id) AS total_criaturas
             FROM campanhas c
             ORDER BY c.atualizado_em DESC, c.nome ASC'
        );
        return $stmt === false ? [] : $stmt->fetchAll();
    }

    /**
     * Busca uma campanha pelo ID.
     *
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, sistema, descricao, capa_arquivo, criado_em, atualizado_em
             FROM campanhas
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $linha = $stmt->fetch();
        return $linha === false ? null : $linha;
    }

    /**
     * @param array{nome: string, sistema: string, descricao: string, capa_arquivo?: string|null} $dados
     */
    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO campanhas (nome, sistema, descricao, capa_arquivo)
             VALUES (:nome, :sistema, :descricao, :capa_arquivo)'
        );
        $stmt->execute([
            ':nome'         => $dados['nome'],
            ':sistema'      => $dados['sistema'],
            ':descricao'    => $dados['descricao'],
            ':capa_arquivo' => $dados['capa_arquivo'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualiza campos. Se 'capa_arquivo' não estiver presente no array,
     * a coluna existente é PRESERVADA (não há perda da capa atual quando o
     * usuário edita o formulário sem reenviar foto).
     *
     * Para REMOVER a capa, passe explicitamente 'capa_arquivo' => null.
     *
     * @param array{nome: string, sistema: string, descricao: string, capa_arquivo?: string|null} $dados
     */
    public function atualizar(int $id, array $dados): bool
    {
        $sets   = ['nome = :nome', 'sistema = :sistema', 'descricao = :descricao'];
        $params = [
            ':nome'      => $dados['nome'],
            ':sistema'   => $dados['sistema'],
            ':descricao' => $dados['descricao'],
        ];
        if (array_key_exists('capa_arquivo', $dados)) {
            $sets[]                  = 'capa_arquivo = :capa_arquivo';
            $params[':capa_arquivo'] = $dados['capa_arquivo'];
        }

        $sql  = 'UPDATE campanhas SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor);
        }
        return $stmt->execute();
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM campanhas WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function contar(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM campanhas');
        return $stmt === false ? 0 : (int) $stmt->fetchColumn();
    }
}
