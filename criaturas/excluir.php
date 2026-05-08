<?php
declare(strict_types=1);

/**
 * Handler de exclusão de criatura. Aceita apenas POST e exige token CSRF válido.
 * Após processar, redireciona sempre para a listagem (Post-Redirect-Get).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/CriaturaRepositorio.php';

iniciarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Location: ' . url('/criaturas/listar.php'));
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    definirFlash('erro', 'Token de seguranca invalido. Operacao abortada.');
    header('Location: ' . url('/criaturas/listar.php'));
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    definirFlash('erro', 'Identificador de criatura invalido.');
    header('Location: ' . url('/criaturas/listar.php'));
    exit;
}

try {
    $repo = new CriaturaRepositorio();
    $alvo = $repo->buscarPorId($id);
    if ($alvo === null) {
        definirFlash('aviso', "Criatura #{$id} ja nao existia mais nos arquivos.");
    } else {
        $repo->excluir($id);
        definirFlash(
            'sucesso',
            sprintf("Criatura '%s' (#%d) expurgada do Bestiario.", $alvo['nome'], $id)
        );
    }
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao excluir criatura: ' . $e->getMessage());
}

header('Location: ' . url('/criaturas/listar.php'));
exit;
