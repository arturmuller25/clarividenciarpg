<?php
declare(strict_types=1);

/**
 * Exclui uma campanha. Apaga também o arquivo físico de capa, se houver.
 *
 * NPCs/Criaturas/Agentes vinculados ficam com campanha_id = NULL (efeito do
 * ON DELETE SET NULL definido nas FKs). Não há cascata destrutiva.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/CampanhaRepositorio.php';
require_once __DIR__ . '/../src/UploadHelper.php';

iniciarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Location: ' . url('/campanhas/listar.php'));
    exit;
}

if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    definirFlash('erro', 'Token de segurança inválido. Operação abortada.');
    header('Location: ' . url('/campanhas/listar.php'));
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    definirFlash('erro', 'Identificador de campanha inválido.');
    header('Location: ' . url('/campanhas/listar.php'));
    exit;
}

try {
    $repo = new CampanhaRepositorio();
    $alvo = $repo->buscarPorId($id);
    if ($alvo === null) {
        definirFlash('aviso', "Campanha #{$id} já não existia mais nos arquivos.");
    } else {
        $repo->excluir($id);
        // Após o DELETE no banco, apaga o arquivo da capa
        if (!empty($alvo['capa_arquivo'])) {
            UploadHelper::apagarImagem('campanhas', (string) $alvo['capa_arquivo']);
        }
        definirFlash(
            'sucesso',
            sprintf("Campanha '%s' (#%d) arquivada. Vínculos preservados sem campanha.",
                    $alvo['nome'], $id)
        );
    }
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao arquivar campanha: ' . $e->getMessage());
}

header('Location: ' . url('/campanhas/listar.php'));
exit;
