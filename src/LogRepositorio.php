<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Repositório do log de rolagens (Diário de Campanha).
 *
 * Toda operação usa Prepared Statements reais.
 */
final class LogRepositorio
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getConexao();
    }

    /**
     * Persiste uma rolagem.
     *
     * @param array{
     *     quem_rolou: string,
     *     descricao: string,
     *     quantidade_dados: int,
     *     resultados_brutos: array<int, int>,
     *     resultado_final: int,
     *     eh_critico: bool,
     *     eh_desastre: bool
     * } $registro
     */
    public function registrar(array $registro): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO log_rolagens
                (quem_rolou, descricao, quantidade_dados, resultados_brutos,
                 resultado_final, eh_critico, eh_desastre)
             VALUES
                (:quem_rolou, :descricao, :quantidade_dados, :resultados_brutos,
                 :resultado_final, :eh_critico, :eh_desastre)'
        );
        $stmt->bindValue(':quem_rolou',        $registro['quem_rolou']);
        $stmt->bindValue(':descricao',         $registro['descricao']);
        $stmt->bindValue(':quantidade_dados',  $registro['quantidade_dados'], PDO::PARAM_INT);
        $stmt->bindValue(':resultados_brutos',
            json_encode(array_values($registro['resultados_brutos']), JSON_THROW_ON_ERROR));
        $stmt->bindValue(':resultado_final',   $registro['resultado_final'],  PDO::PARAM_INT);
        $stmt->bindValue(':eh_critico',        $registro['eh_critico']  ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':eh_desastre',       $registro['eh_desastre'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Lista as rolagens mais recentes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarRecentes(int $limite = 100): array
    {
        $limite = max(1, min(500, $limite));
        $stmt = $this->pdo->prepare(
            'SELECT id, quem_rolou, descricao, quantidade_dados, resultados_brutos,
                    resultado_final, eh_critico, eh_desastre, rolado_em
             FROM log_rolagens
             ORDER BY rolado_em DESC, id DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
