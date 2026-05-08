<?php
declare(strict_types=1);

/**
 * Formulário CRIAR/EDITAR ficha completa de Agente.
 *
 * Estrutura:
 *  - Cada seção é um partial em /agentes/partials/_*.php (9 seções).
 *  - Tabelas-filhas (perícias, ataques, inventário, rituais) são salvas
 *    transacionalmente pelo AgenteRepositorio (DELETE + INSERT).
 *  - Upload de foto opcional com UploadHelper (MIME via finfo, etc.).
 *
 * Acesso:
 *  - GET sem ?id  → criação com defaults sensatos.
 *  - GET com ?id  → edição com a ficha completa carregada do banco.
 *  - POST         → valida, processa upload, persiste, redireciona p/ listar.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/AgenteRepositorio.php';
require_once __DIR__ . '/../src/AgenteValidador.php';
require_once __DIR__ . '/../src/PericiaCatalog.php';
require_once __DIR__ . '/../src/CampanhaRepositorio.php';
require_once __DIR__ . '/../src/UploadHelper.php';

iniciarSessao();

$repo          = new AgenteRepositorio();
$campanhasRepo = new CampanhaRepositorio();
$campanhasOpts = $campanhasRepo->listar();

$id         = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modoEdicao = $id > 0;

// Defaults para criação
$dados = [
    'agente' => [
        'campanha_id'   => null,
        'nome'          => '',
        'jogador'       => '',
        'origem'        => '',
        'classe'        => 'Combatente',
        'nex'           => 5,
        'pv_atual'      => 8,  'pv_maximo'  => 8,
        'san_atual'     => 4,  'san_maximo' => 4,
        'pe_atual'      => 2,  'pe_maximo'  => 2,
        'forca'         => 1, 'agilidade' => 1, 'intelecto' => 1, 'vigor' => 1, 'presenca' => 1,
        'pe_por_turno'  => 2, 'deslocamento' => 9, 'defesa' => 10,
        'resistencias'  => '', 'proficiencias' => '',
        'aparencia'     => '', 'personalidade' => '',
        'historico'     => '', 'objetivos'     => '',
    ],
    'pericias'   => [],
    'ataques'    => [],
    'inventario' => [],
    'rituais'    => [],
];
$fotoAtual = null;
$erros     = [];

if ($modoEdicao && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $ficha = $repo->buscarFichaCompleta($id);
    if ($ficha === null) {
        definirFlash('erro', "Agente #{$id} não encontrado.");
        header('Location: ' . url('/agentes/listar.php'));
        exit;
    }
    $dados = $ficha;
    $fotoAtual = $ficha['agente']['foto_arquivo'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCsrf($_POST['csrf_token'] ?? null)) {
        definirFlash('erro', 'Token de segurança inválido.');
        header('Location: ' . url($modoEdicao ? "/agentes/formulario.php?id={$id}" : '/agentes/formulario.php'));
        exit;
    }

    $resultado = AgenteValidador::validar($_POST);
    $dados     = $resultado['dados'];
    $erros     = $resultado['erros'];

    // Recuperar foto atual antes de tomar qualquer decisão sobre upload
    if ($modoEdicao) {
        $atualDb   = $repo->buscarPorId($id);
        $fotoAtual = $atualDb['foto_arquivo'] ?? null;
    }

    if (empty($erros)) {
        try {
            $novaFoto      = null;
            $removerFoto   = isset($_POST['remover_foto']) && $_POST['remover_foto'] === '1';
            $tentouEnviar  = !empty($_FILES['foto']['name'] ?? '');

            if ($tentouEnviar) {
                $novaFoto = UploadHelper::moverImagem($_FILES['foto'] ?? null, 'agentes');
            }

            // Decide o valor de foto_arquivo a persistir:
            //   nada enviado E não removendo → não tocar (deixar fora do array)
            //   nova foto enviada            → substituir, apagar antiga do disco
            //   só removerFoto marcado       → setar null, apagar antiga do disco
            if ($novaFoto !== null) {
                $dados['agente']['foto_arquivo'] = $novaFoto;
                if ($modoEdicao && is_string($fotoAtual) && $fotoAtual !== '') {
                    UploadHelper::apagarImagem('agentes', $fotoAtual);
                }
            } elseif ($removerFoto) {
                $dados['agente']['foto_arquivo'] = null;
                if ($modoEdicao && is_string($fotoAtual) && $fotoAtual !== '') {
                    UploadHelper::apagarImagem('agentes', $fotoAtual);
                }
            }
            // Se não foi enviado e não está removendo, NÃO seta a chave foto_arquivo
            // — o repositório preserva a coluna existente.

            if ($modoEdicao) {
                $repo->atualizar($id, $dados);
                definirFlash('sucesso', "Agente #{$id} atualizado.");
            } else {
                $novoId = $repo->criar($dados);
                definirFlash('sucesso', "Agente #{$novoId} registrado.");
            }
            header('Location: ' . url('/agentes/listar.php'));
            exit;
        } catch (Throwable $e) {
            $erros['_geral'] = 'Falha ao salvar: ' . $e->getMessage();
        }
    } else {
        definirFlash('erro', 'Verifique os campos destacados.');
    }
}

$titulo      = $modoEdicao ? 'EDITAR_AGENTE' : 'NOVO_AGENTE';
$paginaAtiva = 'agentes';
$fotoUrl     = UploadHelper::urlImagem('agentes', $fotoAtual);

require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        <?= $modoEdicao
            ? 'EDITAR AGENTE #' . str_pad((string) $id, 4, '0', STR_PAD_LEFT)
            : 'NOVO AGENTE' ?>
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        Ficha completa do personagem jogador (PJ). Cada seção pode ser expandida ou recolhida.
    </p>
</section>

<?php if (!empty($erros['_geral'])): ?>
    <div class="flash flash--erro">
        <span class="flash__rotulo">[ERRO]</span>
        <span class="flash__texto"><?= escapar($erros['_geral']) ?></span>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="formulario formulario--ficha"
      data-form-agente novalidate data-validar-formulario>
    <input type="hidden" name="csrf_token" value="<?= escapar(gerarTokenCsrf()) ?>">

    <?php require __DIR__ . '/partials/_identidade.php'; ?>
    <?php require __DIR__ . '/partials/_barras.php'; ?>
    <?php require __DIR__ . '/partials/_atributos.php'; ?>
    <?php require __DIR__ . '/partials/_defesa.php'; ?>
    <?php require __DIR__ . '/partials/_narrativa.php'; ?>
    <?php require __DIR__ . '/partials/_pericias.php'; ?>
    <?php require __DIR__ . '/partials/_ataques.php'; ?>
    <?php require __DIR__ . '/partials/_inventario.php'; ?>
    <?php require __DIR__ . '/partials/_rituais.php'; ?>

    <div class="formulario__acoes ficha-acoes">
        <button type="submit" class="botao botao--primario">
            <?= $modoEdicao ? 'ATUALIZAR FICHA' : 'REGISTRAR AGENTE' ?>
        </button>
        <a href="<?= escapar(url('/agentes/listar.php')) ?>" class="botao botao--secundario">CANCELAR</a>
    </div>
</form>

<?php require __DIR__ . '/../views/rodape.php'; ?>
