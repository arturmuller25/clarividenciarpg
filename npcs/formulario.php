<?php
declare(strict_types=1);

/**
 * Formulário compartilhado para CRIAR e EDITAR NPCs.
 *
 * - GET sem ?id   -> formulário em branco (criação).
 * - GET com ?id   -> formulário pré-preenchido (edição).
 * - POST          -> processa criação ou atualização (mesmo handler).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/NpcRepositorio.php';
require_once __DIR__ . '/../src/NpcValidador.php';

iniciarSessao();

$repo       = new NpcRepositorio();
$id         = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modoEdicao = $id > 0;

$dados = [
    'nome'        => '',
    'ocupacao'    => '',
    'localizacao' => '',
    'atitude'     => 'Neutro',
    'historia'    => '',
];
$erros = [];

if ($modoEdicao && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $existente = $repo->buscarPorId($id);
    if ($existente === null) {
        definirFlash('erro', "NPC #{$id} nao encontrado nos arquivos.");
        header('Location: /npcs/listar.php');
        exit;
    }
    $dados = [
        'nome'        => (string) $existente['nome'],
        'ocupacao'    => (string) $existente['ocupacao'],
        'localizacao' => (string) $existente['localizacao'],
        'atitude'     => (string) $existente['atitude'],
        'historia'    => (string) $existente['historia'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        definirFlash('erro', 'Token de seguranca invalido. Tente novamente.');
        $destino = $modoEdicao ? "/npcs/formulario.php?id={$id}" : '/npcs/formulario.php';
        header('Location: ' . $destino);
        exit;
    }

    $resultado = NpcValidador::validar($_POST);
    $dados = $resultado['dados'];
    $erros = $resultado['erros'];

    if (empty($erros)) {
        try {
            if ($modoEdicao) {
                $repo->atualizar($id, $dados);
                definirFlash('sucesso', "Sincronia com o Outro Lado estabelecida. NPC #{$id} atualizado.");
            } else {
                $novoId = $repo->criar($dados);
                definirFlash('sucesso', "Dossie #{$novoId} arquivado. Os Arquivos da Ordem reconhecem o registro.");
            }
            header('Location: /npcs/listar.php');
            exit;
        } catch (Throwable $e) {
            $erros['_geral'] = 'Falha na Manifestacao: ' . $e->getMessage();
        }
    } else {
        definirFlash('erro', 'Falha na Manifestacao. Verifique os campos destacados.');
    }
}

$titulo      = $modoEdicao ? 'EDITAR_NPC' : 'NOVO_NPC';
$paginaAtiva = 'npcs';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        <?= $modoEdicao
            ? 'EDITAR DOSSIE #' . str_pad((string) $id, 4, '0', STR_PAD_LEFT)
            : 'NOVO DOSSIE' ?>
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        <?= $modoEdicao
            ? 'Modifique os campos abaixo e confirme a atualizacao.'
            : 'Preencha os campos para registrar um novo NPC nos arquivos.' ?>
    </p>
</section>

<?php if (!empty($erros['_geral'])): ?>
    <div class="flash flash--erro">
        <span class="flash__rotulo">[ERRO]</span>
        <span class="flash__texto"><?= escapar($erros['_geral']) ?></span>
    </div>
<?php endif; ?>

<form method="POST" class="formulario" novalidate data-validar-formulario>
    <input type="hidden" name="csrf_token" value="<?= escapar(gerarTokenCsrf()) ?>">

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

    <div class="campo <?= isset($erros['ocupacao']) ? 'campo--invalido' : '' ?>">
        <label for="ocupacao" class="campo__rotulo">
            <span class="campo__indice">02.</span> OCUPACAO
            <span class="campo__obrigatorio" aria-hidden="true">*</span>
        </label>
        <input type="text" id="ocupacao" name="ocupacao" class="campo__entrada"
               value="<?= escapar($dados['ocupacao']) ?>" maxlength="120" required
               autocomplete="off" aria-describedby="erro-ocupacao"
               aria-invalid="<?= isset($erros['ocupacao']) ? 'true' : 'false' ?>">
        <small id="erro-ocupacao" class="campo__erro" data-erro-para="ocupacao">
            <?= isset($erros['ocupacao']) ? escapar($erros['ocupacao']) : '' ?>
        </small>
    </div>

    <div class="formulario__linha">
        <div class="campo <?= isset($erros['localizacao']) ? 'campo--invalido' : '' ?>">
            <label for="localizacao" class="campo__rotulo">
                <span class="campo__indice">03.</span> LOCALIZACAO
                <span class="campo__obrigatorio" aria-hidden="true">*</span>
            </label>
            <input type="text" id="localizacao" name="localizacao" class="campo__entrada"
                   value="<?= escapar($dados['localizacao']) ?>" maxlength="120" required
                   autocomplete="off" aria-describedby="erro-localizacao"
                   aria-invalid="<?= isset($erros['localizacao']) ? 'true' : 'false' ?>">
            <small id="erro-localizacao" class="campo__erro" data-erro-para="localizacao">
                <?= isset($erros['localizacao']) ? escapar($erros['localizacao']) : '' ?>
            </small>
        </div>

        <div class="campo <?= isset($erros['atitude']) ? 'campo--invalido' : '' ?>">
            <label for="atitude" class="campo__rotulo">
                <span class="campo__indice">04.</span> ATITUDE
                <span class="campo__obrigatorio" aria-hidden="true">*</span>
            </label>
            <select id="atitude" name="atitude" class="campo__entrada" required
                    aria-describedby="erro-atitude"
                    aria-invalid="<?= isset($erros['atitude']) ? 'true' : 'false' ?>">
                <?php foreach (NpcRepositorio::ATITUDES as $opcao): ?>
                    <option value="<?= escapar($opcao) ?>"
                        <?= $dados['atitude'] === $opcao ? 'selected' : '' ?>>
                        <?= escapar(strtoupper($opcao)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small id="erro-atitude" class="campo__erro" data-erro-para="atitude">
                <?= isset($erros['atitude']) ? escapar($erros['atitude']) : '' ?>
            </small>
        </div>
    </div>

    <div class="campo <?= isset($erros['historia']) ? 'campo--invalido' : '' ?>">
        <label for="historia" class="campo__rotulo">
            <span class="campo__indice">05.</span> HISTORIA
            <span class="campo__obrigatorio" aria-hidden="true">*</span>
        </label>
        <textarea id="historia" name="historia"
                  class="campo__entrada campo__entrada--textarea"
                  rows="8" minlength="10" maxlength="5000" required
                  aria-describedby="erro-historia ajuda-historia"
                  aria-invalid="<?= isset($erros['historia']) ? 'true' : 'false' ?>"><?= escapar($dados['historia']) ?></textarea>
        <small id="ajuda-historia" class="campo__ajuda">
            Minimo 10 caracteres. <span data-contador-de="historia">0</span>/5000.
        </small>
        <small id="erro-historia" class="campo__erro" data-erro-para="historia">
            <?= isset($erros['historia']) ? escapar($erros['historia']) : '' ?>
        </small>
    </div>

    <div class="formulario__acoes">
        <button type="submit" class="botao botao--primario">
            <?= $modoEdicao ? 'ATUALIZAR DOSSIE' : 'REGISTRAR NPC' ?>
        </button>
        <a href="/npcs/listar.php" class="botao botao--secundario">CANCELAR</a>
    </div>
</form>

<?php require __DIR__ . '/../views/rodape.php'; ?>
