<?php
declare(strict_types=1);

/**
 * Formulário compartilhado para CRIAR e EDITAR criaturas do Bestiário.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/CriaturaRepositorio.php';
require_once __DIR__ . '/../src/CriaturaValidador.php';
require_once __DIR__ . '/../src/CampanhaRepositorio.php';

iniciarSessao();

$repo          = new CriaturaRepositorio();
$campanhaRepo  = new CampanhaRepositorio();
$campanhasOpts = $campanhaRepo->listar();
$id            = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modoEdicao    = $id > 0;

$dados = [
    'campanha_id'  => null,
    'nome'         => '',
    'elemento'     => 'Sangue',
    'vd'           => 1.0,
    'pv_atual'     => 10,
    'pv_maximo'    => 10,
    'habilidades'  => '',
    'foto_arquivo' => null,
];
$fotoAtual = null;
$erros     = [];

if ($modoEdicao && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $existente = $repo->buscarPorId($id);
    if ($existente === null) {
        definirFlash('erro', "Criatura #{$id} não encontrada nos arquivos.");
        header('Location: ' . url('/criaturas/listar.php'));
        exit;
    }
    $dados = [
        'campanha_id'  => isset($existente['campanha_id']) ? (int) $existente['campanha_id'] : null,
        'nome'         => (string) $existente['nome'],
        'elemento'     => (string) $existente['elemento'],
        'vd'           => (float)  $existente['vd'],
        'pv_atual'     => (int)    $existente['pv_atual'],
        'pv_maximo'    => (int)    $existente['pv_maximo'],
        'habilidades'  => (string) $existente['habilidades'],
        'foto_arquivo' => $existente['foto_arquivo'] ?? null,
    ];
    $fotoAtual = $existente['foto_arquivo'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        definirFlash('erro', 'Token de segurança inválido. Tente novamente.');
        $destino = $modoEdicao
            ? url('/criaturas/formulario.php?id=' . $id)
            : url('/criaturas/formulario.php');
        header('Location: ' . $destino);
        exit;
    }

    $resultado = CriaturaValidador::validar($_POST);
    $dados = $resultado['dados'];
    $erros = $resultado['erros'];

    if ($modoEdicao) {
        $atualDb   = $repo->buscarPorId($id);
        $fotoAtual = $atualDb['foto_arquivo'] ?? null;
    }

    if (empty($erros)) {
        try {
            // ---------- Foto ----------
            $novaFoto     = null;
            $removerFoto  = isset($_POST['remover_foto']) && $_POST['remover_foto'] === '1';
            $tentouEnviar = !empty($_FILES['foto']['name'] ?? '');

            if ($tentouEnviar) {
                $novaFoto = UploadHelper::moverImagem($_FILES['foto'] ?? null, 'criaturas');
            }
            if ($novaFoto !== null) {
                $dados['foto_arquivo'] = $novaFoto;
                if ($modoEdicao && is_string($fotoAtual) && $fotoAtual !== '') {
                    UploadHelper::apagarImagem('criaturas', $fotoAtual);
                }
            } elseif ($removerFoto) {
                $dados['foto_arquivo'] = null;
                if ($modoEdicao && is_string($fotoAtual) && $fotoAtual !== '') {
                    UploadHelper::apagarImagem('criaturas', $fotoAtual);
                }
            } else {
                unset($dados['foto_arquivo']);
            }

            if ($modoEdicao) {
                $repo->atualizar($id, $dados);
                definirFlash('sucesso', "Sincronia com o Outro Lado estabelecida. Criatura #{$id} atualizada.");
            } else {
                $novoId = $repo->criar($dados);
                definirFlash('sucesso', "Criatura #{$novoId} catalogada. O Bestiário reconhece a ameaça.");
            }
            header('Location: ' . url('/criaturas/listar.php'));
            exit;
        } catch (Throwable $e) {
            $erros['_geral'] = 'Falha na Manifestação: ' . $e->getMessage();
        }
    } else {
        definirFlash('erro', 'Falha na Manifestação. Verifique os campos destacados.');
    }
}

$fotoUrl = UploadHelper::urlImagem('criaturas', $fotoAtual);

$titulo      = $modoEdicao ? 'EDITAR_CRIATURA' : 'NOVA_CRIATURA';
$paginaAtiva = 'bestiario';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        <?= $modoEdicao
            ? 'EDITAR CRIATURA #' . str_pad((string) $id, 4, '0', STR_PAD_LEFT)
            : 'NOVA CRIATURA' ?>
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        <?= $modoEdicao
            ? 'Atualize os parâmetros da criatura conforme novas evidências.'
            : 'Catalogue uma nova ameaça paranormal nos arquivos do Bestiário.' ?>
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

    <div class="campo">
        <label class="campo__rotulo">
            <span class="campo__indice">00.</span> FOTO DA CRIATURA
            <small class="campo__ajuda" style="margin-left: 8px">Recortada automaticamente em 1:1.</small>
        </label>
        <div data-cropper data-cropper-input="foto"
             <?php if ($fotoUrl): ?>data-cropper-existing="<?= escapar($fotoUrl) ?>"<?php endif; ?>>
            <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="campo__entrada campo__entrada--arquivo">
            <?php if ($fotoUrl): ?>
                <label class="upload-preview__remover" style="margin-top: 6px">
                    <input type="checkbox" name="remover_foto" value="1">
                    <span>Remover foto atual</span>
                </label>
            <?php endif; ?>
        </div>
    </div>

    <div class="campo">
        <label for="campanha_id" class="campo__rotulo">
            <span class="campo__indice">00b.</span> CAMPANHA VINCULADA
        </label>
        <select id="campanha_id" name="campanha_id" class="campo__entrada">
            <option value="">[ SEM CAMPANHA ]</option>
            <?php foreach ($campanhasOpts as $c): ?>
                <option value="<?= (int) $c['id'] ?>"
                    <?= (int) $dados['campanha_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= escapar((string) $c['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="campo__ajuda">
            Opcional. Vincula esta ameaça a uma operação específica.
        </small>
    </div>

    <div class="campo <?= isset($erros['nome']) ? 'campo--invalido' : '' ?>">
        <label for="nome" class="campo__rotulo">
            <span class="campo__indice">01.</span> NOME
            <span class="campo__obrigatorio" aria-hidden="true">*</span>
        </label>
        <input type="text" id="nome" name="nome" class="campo__entrada"
               value="<?= escapar($dados['nome']) ?>" maxlength="120" required
               autocomplete="off" aria-describedby="erro-nome"
               aria-invalid="<?= isset($erros['nome']) ? 'true' : 'false' ?>">
        <small id="erro-nome" class="campo__erro" data-erro-para="nome">
            <?= isset($erros['nome']) ? escapar($erros['nome']) : '' ?>
        </small>
    </div>

    <div class="formulario__linha">
        <div class="campo <?= isset($erros['elemento']) ? 'campo--invalido' : '' ?>">
            <label for="elemento" class="campo__rotulo">
                <span class="campo__indice">02.</span> ELEMENTO
                <span class="campo__obrigatorio" aria-hidden="true">*</span>
            </label>
            <select id="elemento" name="elemento" class="campo__entrada" required
                    aria-describedby="erro-elemento"
                    aria-invalid="<?= isset($erros['elemento']) ? 'true' : 'false' ?>">
                <?php foreach (CriaturaRepositorio::ELEMENTOS as $opcao): ?>
                    <option value="<?= escapar($opcao) ?>"
                        <?= $dados['elemento'] === $opcao ? 'selected' : '' ?>>
                        <?= escapar(strtoupper($opcao)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small id="erro-elemento" class="campo__erro" data-erro-para="elemento">
                <?= isset($erros['elemento']) ? escapar($erros['elemento']) : '' ?>
            </small>
        </div>

        <div class="campo <?= isset($erros['vd']) ? 'campo--invalido' : '' ?>">
            <label for="vd" class="campo__rotulo">
                <span class="campo__indice">03.</span> VD (DESAFIO)
                <span class="campo__obrigatorio" aria-hidden="true">*</span>
            </label>
            <input type="number" id="vd" name="vd" class="campo__entrada"
                   value="<?= escapar((string) $dados['vd']) ?>"
                   step="0.5" min="0" max="30" required
                   aria-describedby="erro-vd"
                   aria-invalid="<?= isset($erros['vd']) ? 'true' : 'false' ?>">
            <small id="erro-vd" class="campo__erro" data-erro-para="vd">
                <?= isset($erros['vd']) ? escapar($erros['vd']) : '' ?>
            </small>
        </div>
    </div>

    <div class="formulario__linha">
        <div class="campo <?= isset($erros['pv_atual']) ? 'campo--invalido' : '' ?>">
            <label for="pv_atual" class="campo__rotulo">
                <span class="campo__indice">04.</span> PV ATUAL
                <span class="campo__obrigatorio" aria-hidden="true">*</span>
            </label>
            <input type="number" id="pv_atual" name="pv_atual" class="campo__entrada"
                   value="<?= escapar((string) $dados['pv_atual']) ?>"
                   min="0" max="9999" required
                   aria-describedby="erro-pv_atual"
                   aria-invalid="<?= isset($erros['pv_atual']) ? 'true' : 'false' ?>">
            <small id="erro-pv_atual" class="campo__erro" data-erro-para="pv_atual">
                <?= isset($erros['pv_atual']) ? escapar($erros['pv_atual']) : '' ?>
            </small>
        </div>

        <div class="campo <?= isset($erros['pv_maximo']) ? 'campo--invalido' : '' ?>">
            <label for="pv_maximo" class="campo__rotulo">
                <span class="campo__indice">05.</span> PV MAXIMO
                <span class="campo__obrigatorio" aria-hidden="true">*</span>
            </label>
            <input type="number" id="pv_maximo" name="pv_maximo" class="campo__entrada"
                   value="<?= escapar((string) $dados['pv_maximo']) ?>"
                   min="0" max="9999" required
                   aria-describedby="erro-pv_maximo"
                   aria-invalid="<?= isset($erros['pv_maximo']) ? 'true' : 'false' ?>">
            <small id="erro-pv_maximo" class="campo__erro" data-erro-para="pv_maximo">
                <?= isset($erros['pv_maximo']) ? escapar($erros['pv_maximo']) : '' ?>
            </small>
        </div>
    </div>

    <div class="campo <?= isset($erros['habilidades']) ? 'campo--invalido' : '' ?>">
        <label for="habilidades" class="campo__rotulo">
            <span class="campo__indice">06.</span> HABILIDADES
            <span class="campo__obrigatorio" aria-hidden="true">*</span>
        </label>
        <textarea id="habilidades" name="habilidades"
                  class="campo__entrada campo__entrada--textarea"
                  rows="8" minlength="5" maxlength="5000" required
                  aria-describedby="erro-habilidades ajuda-habilidades"
                  aria-invalid="<?= isset($erros['habilidades']) ? 'true' : 'false' ?>"><?= escapar($dados['habilidades']) ?></textarea>
        <small id="ajuda-habilidades" class="campo__ajuda">
            Descreva ataques, resistencias, gatilhos. <span data-contador-de="habilidades">0</span>/5000.
        </small>
        <small id="erro-habilidades" class="campo__erro" data-erro-para="habilidades">
            <?= isset($erros['habilidades']) ? escapar($erros['habilidades']) : '' ?>
        </small>
    </div>

    <div class="formulario__acoes">
        <button type="submit" class="botao botao--primario">
            <?= $modoEdicao ? 'ATUALIZAR CRIATURA' : 'CATALOGAR CRIATURA' ?>
        </button>
        <a href="<?= escapar(url('/criaturas/listar.php')) ?>" class="botao botao--secundario">CANCELAR</a>
    </div>
</form>

<?php require __DIR__ . '/../views/rodape.php'; ?>
