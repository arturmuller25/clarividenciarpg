<?php
declare(strict_types=1);

/**
 * Handler de exclusão de NPC. Aceita apenas POST e exige token CSRF válido.
 * Após processar, redireciona sempre para a listagem (padrão Post-Redirect-Get).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/NpcRepositorio.php';

iniciarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Location: /npcs/listar.php');
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    definirFlash('erro', 'Token de seguranca invalido. Operacao abortada.');
    header('Location: /npcs/listar.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    definirFlash('erro', 'Identificador de NPC invalido.');
    header('Location: /npcs/listar.php');
    exit;
}

try {
    $repo = new NpcRepositorio();
    $alvo = $repo->buscarPorId($id);
    if ($alvo === null) {
        definirFlash('aviso', "NPC #{$id} nao existia mais nos arquivos.");
    } else {
        $repo->excluir($id);
        definirFlash(
            'sucesso',
            sprintf("NPC '%s' (#%d) removido dos arquivos da Ordem.", $alvo['nome'], $id)
        );
    }
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao excluir NPC: ' . $e->getMessage());
}

header('Location: /npcs/listar.php');
exit;
