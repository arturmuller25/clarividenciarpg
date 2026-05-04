<?php
declare(strict_types=1);

/**
 * Endpoint POST: persiste uma rolagem feita pelo cliente em log_rolagens.
 *
 * Aceita apenas POST. Exige CSRF válido. Revalida tudo no servidor:
 * recalcula resultado_final, eh_critico e eh_desastre a partir dos dados crus,
 * para impedir adulteração via DevTools.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/LogRepositorio.php';

iniciarSessao();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'erro' => 'METODO_NAO_PERMITIDO']);
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'erro' => 'CSRF_INVALIDO']);
    exit;
}

$quemRolou       = trim((string) ($_POST['quem_rolou']        ?? ''));
$descricao       = trim((string) ($_POST['descricao']         ?? ''));
$quantidadeBruta = (string)        ($_POST['quantidade_dados'] ?? '');
$brutosBruto     = (string)        ($_POST['resultados_brutos']?? '');

$erros = [];

if ($quemRolou === '' || mb_strlen($quemRolou) > 120) {
    $erros[] = 'quem_rolou invalido';
}
if ($descricao === '' || mb_strlen($descricao) > 160) {
    $erros[] = 'descricao invalida';
}
if (!ctype_digit($quantidadeBruta)) {
    $erros[] = 'quantidade invalida';
}
$quantidade = $erros ? 0 : (int) $quantidadeBruta;
if ($quantidade < 0 || $quantidade > 10) {
    $erros[] = 'quantidade fora do intervalo permitido (0-10)';
}

$brutos = [];
try {
    $decod = json_decode($brutosBruto, true, 4, JSON_THROW_ON_ERROR);
    if (!is_array($decod)) {
        throw new RuntimeException('lista de resultados invalida');
    }
    foreach ($decod as $valor) {
        if (!is_int($valor) || $valor < 1 || $valor > 20) {
            throw new RuntimeException('um dos dados esta fora de 1-20');
        }
        $brutos[] = $valor;
    }
} catch (Throwable $e) {
    $erros[] = 'resultados_brutos: ' . $e->getMessage();
}

// Coerência: quantidade de dados rolados deve casar com a regra
$quantidadeEsperada = $quantidade === 0 ? 2 : $quantidade;
if (!$erros && count($brutos) !== $quantidadeEsperada) {
    $erros[] = sprintf(
        'esperado %d dados, recebidos %d',
        $quantidadeEsperada,
        count($brutos)
    );
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erros' => $erros], JSON_UNESCAPED_UNICODE);
    exit;
}

// Recalcula sempre no servidor — nunca confie em valores derivados do cliente.
$resultadoFinal = $quantidade === 0 ? min($brutos) : max($brutos);
$ehCritico      = $resultadoFinal === 20;
$ehDesastre     = $quantidade === 0;

try {
    $repo = new LogRepositorio();
    $idGerado = $repo->registrar([
        'quem_rolou'        => $quemRolou,
        'descricao'         => $descricao,
        'quantidade_dados'  => $quantidade,
        'resultados_brutos' => $brutos,
        'resultado_final'   => $resultadoFinal,
        'eh_critico'        => $ehCritico,
        'eh_desastre'       => $ehDesastre,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'   => false,
        'erro' => 'FALHA_NO_RITUAL: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok'              => true,
    'id'              => $idGerado,
    'resultado_final' => $resultadoFinal,
    'eh_critico'      => $ehCritico,
    'eh_desastre'     => $ehDesastre,
], JSON_UNESCAPED_UNICODE);
