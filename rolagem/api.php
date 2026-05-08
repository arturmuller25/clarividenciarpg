<?php
declare(strict_types=1);

/**
 * Endpoint POST: persiste uma rolagem feita pelo cliente em log_rolagens.
 *
 * Aceita apenas POST. Exige CSRF válido. Revalida tudo no servidor:
 *   - tipo_dado deve estar em {d4,d6,d8,d10,d12,d20,d100}
 *   - Recalcula resultado_final, eh_critico (=20) e eh_desastre (=1) no servidor
 *     a partir dos dados crus, para impedir adulteração via DevTools.
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

/** Lados de cada tipo de dado suportado. */
const DADOS_LADOS = [
    'd4' => 4, 'd6' => 6, 'd8' => 8, 'd10' => 10,
    'd12' => 12, 'd20' => 20, 'd100' => 100,
];

$quemRolou       = trim((string) ($_POST['quem_rolou']        ?? ''));
$descricao       = trim((string) ($_POST['descricao']         ?? ''));
$tipoDado        = (string)        ($_POST['tipo_dado']        ?? 'd20');
$quantidadeBruta = (string)        ($_POST['quantidade_dados'] ?? '');
$brutosBruto     = (string)        ($_POST['resultados_brutos']?? '');

$erros = [];

if ($quemRolou === '' || mb_strlen($quemRolou) > 120) {
    $erros[] = 'quem_rolou invalido';
}
if ($descricao === '' || mb_strlen($descricao) > 160) {
    $erros[] = 'descricao invalida';
}
if (!isset(DADOS_LADOS[$tipoDado])) {
    $erros[] = 'tipo_dado invalido (esperado um de: ' . implode(', ', array_keys(DADOS_LADOS)) . ')';
}
if (!ctype_digit($quantidadeBruta)) {
    $erros[] = 'quantidade invalida';
}
$quantidade = $erros ? 0 : (int) $quantidadeBruta;
if ($quantidade < 0 || $quantidade > 10) {
    $erros[] = 'quantidade fora do intervalo permitido (0-10)';
}

$lados = DADOS_LADOS[$tipoDado] ?? 20;

$brutos = [];
try {
    $decod = json_decode($brutosBruto, true, 4, JSON_THROW_ON_ERROR);
    if (!is_array($decod)) {
        throw new RuntimeException('lista de resultados invalida');
    }
    foreach ($decod as $valor) {
        if (!is_int($valor) || $valor < 1 || $valor > $lados) {
            throw new RuntimeException(sprintf('valor fora de 1-%d para %s', $lados, $tipoDado));
        }
        $brutos[] = $valor;
    }
} catch (Throwable $e) {
    $erros[] = 'resultados_brutos: ' . $e->getMessage();
}

// Coerência: quantidade de dados rolados deve casar com a regra
//   d20: quantidade=0 -> esperado 2; quantidade>=1 -> esperado N
//   outros tipos: sempre 1 dado
$quantidadeEsperada = match (true) {
    $tipoDado === 'd20' && $quantidade === 0 => 2,
    $tipoDado === 'd20'                      => $quantidade,
    default                                  => 1,
};
if (!$erros && count($brutos) !== $quantidadeEsperada) {
    $erros[] = sprintf(
        'esperado %d dados, recebidos %d (tipo %s, atributo %d)',
        $quantidadeEsperada,
        count($brutos),
        $tipoDado,
        $quantidade
    );
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erros' => $erros], JSON_UNESCAPED_UNICODE);
    exit;
}

// Recalcula sempre no servidor — nunca confie em valores derivados do cliente.
$resultadoFinal = match (true) {
    $tipoDado === 'd20' && $quantidade === 0 => min($brutos),
    $tipoDado === 'd20'                      => max($brutos),
    default                                  => $brutos[0],
};

// Crítico/Desastre APENAS no d20 (regra Ordem Paranormal). Outros tipos
// de dado registram a rolagem mas sem flags de crítico/falha.
$ehCritico  = $tipoDado === 'd20' && $resultadoFinal === 20;
$ehDesastre = $tipoDado === 'd20' && $resultadoFinal === 1;

try {
    $repo = new LogRepositorio();
    $idGerado = $repo->registrar([
        'quem_rolou'        => $quemRolou,
        'descricao'         => $descricao,
        'tipo_dado'         => $tipoDado,
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
    'tipo_dado'       => $tipoDado,
    'resultado_final' => $resultadoFinal,
    'eh_critico'      => $ehCritico,
    'eh_desastre'     => $ehDesastre,
], JSON_UNESCAPED_UNICODE);
