<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Repositório de acesso ao Bestiário.
 *
 * Toda operação usa Prepared Statements reais (ATTR_EMULATE_PREPARES = false)
 * para neutralizar tentativas de SQL Injection.
 */
final class CriaturaRepositorio
{
    public const ELEMENTOS = ['Sangue', 'Morte', 'Conhecimento', 'Energia'];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getConexao();
    }

    /**
     * Lista criaturas com filtro opcional por elemento.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(?string $elemento = null): array
    {
        if (is_string($elemento) && in_array($elemento, self::ELEMENTOS, true)) {
            $stmt = $this->pdo->prepare(
                'SELECT id, nome, elemento, vd, pv_atual, pv_maximo, habilidades,
                        criado_em, atualizado_em
                 FROM criaturas
                 WHERE elemento = :elemento
                 ORDER BY vd DESC, nome ASC'
            );
            $stmt->execute([':elemento' => $elemento]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT id, nome, elemento, vd, pv_atual, pv_maximo, habilidades,
                        criado_em, atualizado_em
                 FROM criaturas
                 ORDER BY vd DESC, nome ASC'
            );
            if ($stmt === false) {
                return [];
            }
        }
        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, elemento, vd, pv_atual, pv_maximo, habilidades,
                    criado_em, atualizado_em
             FROM criaturas
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $linha = $stmt->fetch();
        return $linha === false ? null : $linha;
    }

    /**
     * @param array{nome: string, elemento: string, vd: float, pv_atual: int,
     *              pv_maximo: int, habilidades: string} $dados
     */
    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO criaturas (nome, elemento, vd, pv_atual, pv_maximo, habilidades)
             VALUES (:nome, :elemento, :vd, :pv_atual, :pv_maximo, :habilidades)'
        );
        $stmt->bindValue(':nome',        $dados['nome']);
        $stmt->bindValue(':elemento',    $dados['elemento']);
        $stmt->bindValue(':vd',          $dados['vd']);
        $stmt->bindValue(':pv_atual',    $dados['pv_atual'],  PDO::PARAM_INT);
        $stmt->bindValue(':pv_maximo',   $dados['pv_maximo'], PDO::PARAM_INT);
        $stmt->bindValue(':habilidades', $dados['habilidades']);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array{nome: string, elemento: string, vd: float, pv_atual: int,
     *              pv_maximo: int, habilidades: string} $dados
     */
    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE criaturas
             SET nome = :nome,
                 elemento = :elemento,
                 vd = :vd,
                 pv_atual = :pv_atual,
                 pv_maximo = :pv_maximo,
                 habilidades = :habilidades
             WHERE id = :id'
        );
        $stmt->bindValue(':id',          $id, PDO::PARAM_INT);
        $stmt->bindValue(':nome',        $dados['nome']);
        $stmt->bindValue(':elemento',    $dados['elemento']);
        $stmt->bindValue(':vd',          $dados['vd']);
        $stmt->bindValue(':pv_atual',    $dados['pv_atual'],  PDO::PARAM_INT);
        $stmt->bindValue(':pv_maximo',   $dados['pv_maximo'], PDO::PARAM_INT);
        $stmt->bindValue(':habilidades', $dados['habilidades']);
        return $stmt->execute();
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM criaturas WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
