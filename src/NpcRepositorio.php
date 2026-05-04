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
     * Lista NPCs com filtro opcional por atitude e/ou localização.
     *
     * @param array{atitude?: string|null, localizacao?: string|null} $filtros
     * @return array<int, array<string, mixed>>
     */
    public function listar(array $filtros = []): array
    {
        $sql    = 'SELECT id, nome, ocupacao, localizacao, atitude, historia, criado_em, atualizado_em
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

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY nome ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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
            'SELECT id, nome, ocupacao, localizacao, atitude, historia, criado_em, atualizado_em
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
            'INSERT INTO npcs (nome, ocupacao, localizacao, atitude, historia)
             VALUES (:nome, :ocupacao, :localizacao, :atitude, :historia)'
        );
        $stmt->execute([
            ':nome'        => $dados['nome'],
            ':ocupacao'    => $dados['ocupacao'],
            ':localizacao' => $dados['localizacao'],
            ':atitude'     => $dados['atitude'],
            ':historia'    => $dados['historia'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualiza os campos editáveis de um NPC existente.
     *
     * @param array{nome: string, ocupacao: string, localizacao: string, atitude: string, historia: string} $dados
     */
    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE npcs
             SET nome = :nome,
                 ocupacao = :ocupacao,
                 localizacao = :localizacao,
                 atitude = :atitude,
                 historia = :historia
             WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':nome', $dados['nome']);
        $stmt->bindValue(':ocupacao', $dados['ocupacao']);
        $stmt->bindValue(':localizacao', $dados['localizacao']);
        $stmt->bindValue(':atitude', $dados['atitude']);
        $stmt->bindValue(':historia', $dados['historia']);
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
