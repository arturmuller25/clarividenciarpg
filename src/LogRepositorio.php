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
    public const TIPOS_DADO = ['d4', 'd6', 'd8', 'd10', 'd12', 'd20', 'd100'];

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
     *     tipo_dado: string,
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
                (quem_rolou, descricao, tipo_dado, quantidade_dados, resultados_brutos,
                 resultado_final, eh_critico, eh_desastre)
             VALUES
                (:quem_rolou, :descricao, :tipo_dado, :quantidade_dados, :resultados_brutos,
                 :resultado_final, :eh_critico, :eh_desastre)'
        );
        $stmt->bindValue(':quem_rolou',        $registro['quem_rolou']);
        $stmt->bindValue(':descricao',         $registro['descricao']);
        $stmt->bindValue(':tipo_dado',         $registro['tipo_dado']);
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
            'SELECT id, quem_rolou, descricao, tipo_dado, quantidade_dados, resultados_brutos,
                    resultado_final, eh_critico, eh_desastre, rolado_em
             FROM log_rolagens
             ORDER BY rolado_em DESC, id DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Conta rolagens registradas (usado no Dashboard).
     */
    public function contar(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM log_rolagens');
        return $stmt === false ? 0 : (int) $stmt->fetchColumn();
    }

    /**
     * Busca a rolagem mais recente que foi crítica (Nat 20) OU desastre (Nat 1).
     * Usado no card "última atividade crítica" do Painel do Mestre.
     *
     * @return array<string, mixed>|null
     */
    public function buscarUltimaCritica(): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, quem_rolou, descricao, tipo_dado, quantidade_dados, resultados_brutos,
                    resultado_final, eh_critico, eh_desastre, rolado_em
             FROM log_rolagens
             WHERE eh_critico = 1 OR eh_desastre = 1
             ORDER BY rolado_em DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute();
        $linha = $stmt->fetch();
        return $linha === false ? null : $linha;
    }

    /**
     * Conta rolagens por dia nos últimos N dias. Retorna array com 7 inteiros
     * (do mais antigo ao mais recente) — útil para sparkline simples.
     *
     * @return array<int, int>
     */
    public function contarPorDia(int $dias = 7): array
    {
        $dias = max(1, min(30, $dias));
        $stmt = $this->pdo->prepare(
            'SELECT DATE(rolado_em) AS dia, COUNT(*) AS qtd
             FROM log_rolagens
             WHERE rolado_em >= (CURRENT_DATE - INTERVAL :dias_param DAY)
             GROUP BY DATE(rolado_em)
             ORDER BY dia ASC'
        );
        $stmt->bindValue(':dias_param', $dias, PDO::PARAM_INT);
        $stmt->execute();
        $brutos = [];
        foreach ($stmt->fetchAll() as $linha) {
            $brutos[(string) $linha['dia']] = (int) $linha['qtd'];
        }
        // Preenche dias sem rolagem com 0 — mantém eixo consistente
        $resultado = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $dia = date('Y-m-d', strtotime("-{$i} days"));
            $resultado[] = $brutos[$dia] ?? 0;
        }
        return $resultado;
    }
}
