<?php
declare(strict_types=1);

/**
 * Formulário compartilhado para CRIAR e EDITAR campanhas.
 * Inclui upload opcional de capa (multipart/form-data).
 *
 * Fluxo:
 *  - GET sem ?id              → criação (capa opcional).
 *  - GET com ?id              → edição (capa atual exibida; troca opcional).
 *  - POST                     → valida CSRF, dados, processa upload se houver.
 *  - "remover_capa=1" no POST → apaga capa atual da campanha.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/CampanhaRepositorio.php';
require_once __DIR__ . '/../src/CampanhaValidador.php';
require_once __DIR__ . '/../src/UploadHelper.php';

iniciarSessao();

$repo       = new CampanhaRepositorio();
$id         = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modoEdicao = $id > 0;

$dados = [
    'nome'      => '',
    'sistema'   => 'Ordem Paranormal',
    'descricao' => '',
];
$capaAtual = null;
$erros     = [];

if ($modoEdicao && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $existente = $repo->buscarPorId($id);
    if ($existente === null) {
        definirFlash('erro', "Campanha #{$id} não encontrada nos arquivos.");
        header('Location: ' . url('/campanhas/listar.php'));
        exit;
    }
    $dados = [
        'nome'      => (string) $existente['nome'],
        'sistema'   => (string) $existente['sistema'],
        'descricao' => (string) $existente['descricao'],
    ];
    $capaAtual = $existente['capa_arquivo'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        definirFlash('erro', 'Token de segurança inválido. Tente novamente.');
        $destino = $modoEdicao
            ? url('/campanhas/formulario.php?id=' . $id)
            : url('/campanhas/formulario.php');
        header('Location: ' . $destino);
        exit;
    }

    $resultado = CampanhaValidador::validar($_POST);
    $dados     = $resultado['dados'];
    $erros     = $resultado['erros'];

    // Refetch capa atual (sem confiar no estado anterior, em caso de POST)
    if ($modoEdicao) {
        $existente = $repo->buscarPorId($id);
        $capaAtual = $existente['capa_arquivo'] ?? null;
    }

    if (empty($erros)) {
        try {
            // Tratamento da capa
            $novaCapa     = null;
            $removerCapa  = isset($_POST['remover_capa']) && $_POST['remover_capa'] === '1';
            $tentouEnviar = !empty($_FILES['capa']['name'] ?? '');

            if ($tentouEnviar) {
                $novaCapa = UploadHelper::moverImagem($_FILES['capa'] ?? null, 'campanhas');
            }

            $payload = $dados;
            if ($novaCapa !== null) {
                // Upload bem-sucedido: substituir capa antiga e apagar arquivo dela
                $payload['capa_arquivo'] = $novaCapa;
                if ($modoEdicao && is_string($capaAtual) && $capaAtual !== '') {
                    UploadHelper::apagarImagem('campanhas', $capaAtual);
                }
            } elseif ($removerCapa) {
                $payload['capa_arquivo'] = null;
                if ($modoEdicao && is_string($capaAtual) && $capaAtual !== '') {
                    UploadHelper::apagarImagem('campanhas', $capaAtual);
                }
            }
            // Se nada foi enviado E remover_capa não foi marcado, NÃO mexemos
            // na coluna — preserva a capa atual.

            if ($modoEdicao) {
                $repo->atualizar($id, $payload);
                definirFlash('sucesso', "Campanha #{$id} atualizada nos arquivos.");
            } else {
                $novoId = $repo->criar($payload);
                definirFlash('sucesso', "Campanha #{$novoId} registrada. Operação ativa.");
            }
            header('Location: ' . url('/campanhas/listar.php'));
            exit;
        } catch (Throwable $e) {
            $erros['_geral'] = 'Falha na manifestação: ' . $e->getMessage();
        }
    } else {
        definirFlash('erro', 'Falha na manifestação. Verifique os campos destacados.');
    }
}

$titulo      = $modoEdicao ? 'EDITAR_CAMPANHA' : 'NOVA_CAMPANHA';
$paginaAtiva = 'campanhas';
require __DIR__ . '/../views/cabecalho.php';

$capaUrl = UploadHelper::urlImagem('campanhas', $capaAtual);
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        <?= $modoEdicao
            ? 'EDITAR CAMPANHA #' . str_pad((string) $id, 4, '0', STR_PAD_LEFT)
            : 'NOVA CAMPANHA' ?>
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        <?= $modoEdicao
            ? 'Atualize a operação. A capa só é trocada se você enviar uma nova imagem.'
            : 'Inicie uma nova operação da Ordem. Capa é opcional.' ?>
    </p>
</section>

<?php if (!empty($erros['_geral'])): ?>
    <div class="flash flash--erro">
        <span class="flash__rotulo">[ERRO]</span>
        <span class="flash__texto"><?= escapar($erros['_geral']) ?></span>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="formulario" novalidate data-validar-formulario>
    <input type="hidden" name="csrf_token" value="<?= escapar(gerarTokenCsrf()) ?>">

    <div class="campo <?= isset($erros['nome']) ? 'campo--invalido' : '' ?>">
        <label for="nome" class="campo__rotulo">
            <span class="campo__indice">01.</span> NOME DA CAMPANHA
            <span class="campo__obrigatorio" aria-hidden="true">*</span>
        </label>
        <input type="text" id="nome" name="nome" class="campo__entrada"
               value="<?= escapar($dados['nome']) ?>" maxlength="140" required
               autocomplete="off">
        <small class="campo__erro" data-erro-para="nome">
            <?= isset($erros['nome']) ? escapar($erros['nome']) : '' ?>
        </small>
    </div>

    <div class="campo <?= isset($erros['sistema']) ? 'campo--invalido' : '' ?>">
        <label for="sistema" class="campo__rotulo">
            <span class="campo__indice">02.</span> SISTEMA
        </label>
        <input type="text" id="sistema" name="sistema" class="campo__entrada"
               value="<?= escapar($dados['sistema']) ?>" maxlength="60"
               placeholder="Ordem Paranormal" autocomplete="off">
        <small class="campo__ajuda">Por padrão, "Ordem Paranormal".</small>
        <small class="campo__erro" data-erro-para="sistema">
            <?= isset($erros['sistema']) ? escapar($erros['sistema']) : '' ?>
        </small>
    </div>

    <div class="campo">
        <label class="campo__rotulo">
            <span class="campo__indice">03.</span> CAPA DA CAMPANHA
            <small class="campo__ajuda" style="margin-left: 8px">Recortada em 1:1. JPG, PNG ou WebP. Máx. 4 MB.</small>
        </label>
        <div data-cropper data-cropper-input="capa"
             <?php if ($capaUrl): ?>data-cropper-existing="<?= escapar($capaUrl) ?>"<?php endif; ?>>
            <input type="file" id="capa" name="capa" class="campo__entrada campo__entrada--arquivo"
                   accept="image/jpeg,image/png,image/webp">
            <?php if ($capaUrl): ?>
                <label class="upload-preview__remover" style="margin-top: 6px">
                    <input type="checkbox" name="remover_capa" value="1">
                    <span>Remover capa atual</span>
                </label>
            <?php endif; ?>
        </div>
    </div>

    <div class="campo <?= isset($erros['descricao']) ? 'campo--invalido' : '' ?>">
        <label for="descricao" class="campo__rotulo">
            <span class="campo__indice">04.</span> DESCRIÇÃO
            <span class="campo__obrigatorio" aria-hidden="true">*</span>
        </label>
        <textarea id="descricao" name="descricao"
                  class="campo__entrada campo__entrada--textarea"
                  rows="6" minlength="10" maxlength="5000" required
                  aria-describedby="ajuda-descricao"><?= escapar($dados['descricao']) ?></textarea>
        <small id="ajuda-descricao" class="campo__ajuda">
            Premissa, tom, estado atual da operação. <span data-contador-de="descricao">0</span>/5000.
        </small>
        <small class="campo__erro" data-erro-para="descricao">
            <?= isset($erros['descricao']) ? escapar($erros['descricao']) : '' ?>
        </small>
    </div>

    <div class="formulario__acoes">
        <button type="submit" class="botao botao--primario">
            <?= $modoEdicao ? 'ATUALIZAR CAMPANHA' : 'REGISTRAR CAMPANHA' ?>
        </button>
        <a href="<?= escapar(url('/campanhas/listar.php')) ?>" class="botao botao--secundario">CANCELAR</a>
    </div>
</form>

<?php require __DIR__ . '/../views/rodape.php'; ?>
