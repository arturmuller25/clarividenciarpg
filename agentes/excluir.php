<?php
declare(strict_types=1);

/**
 * Exclui um agente. Apaga também o arquivo físico de foto, se houver.
 *
 * Tabelas filhas (perícias, ataques, inventário, rituais) são removidas
 * automaticamente via ON DELETE CASCADE definido nas FKs (migration_003).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/AgenteRepositorio.php';
require_once __DIR__ . '/../src/UploadHelper.php';

iniciarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Location: ' . url('/agentes/listar.php'));
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    definirFlash('erro', 'Token de segurança inválido. Operação abortada.');
    header('Location: ' . url('/agentes/listar.php'));
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    definirFlash('erro', 'Identificador de agente inválido.');
    header('Location: ' . url('/agentes/listar.php'));
    exit;
}

try {
    $repo = new AgenteRepositorio();
    $alvo = $repo->buscarPorId($id);
    if ($alvo === null) {
        definirFlash('aviso', "Agente #{$id} já não existia mais nos arquivos.");
    } else {
        $repo->excluir($id);
        if (!empty($alvo['foto_arquivo'])) {
            UploadHelper::apagarImagem('agentes', (string) $alvo['foto_arquivo']);
        }
        definirFlash(
            'sucesso',
            sprintf("Agente '%s' (#%d) arquivado. Perícias, ataques, inventário e rituais removidos em cascata.",
                    $alvo['nome'], $id)
        );
    }
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao arquivar agente: ' . $e->getMessage());
}

header('Location: ' . url('/agentes/listar.php'));
exit;
