<?php
declare(strict_types=1);

/**
 * Perfil individual de Criatura. Exibe foto (1:1), VD, PV, elemento e
 * habilidades em formato de leitura — análogo a npcs/visualizar.php.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/CriaturaRepositorio.php';
require_once __DIR__ . '/../src/CampanhaRepositorio.php';
require_once __DIR__ . '/../src/UploadHelper.php';

iniciarSessao();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    definirFlash('erro', 'Identificador de criatura inválido.');
    header('Location: ' . url('/criaturas/listar.php'));
    exit;
}

try {
    $repo = new CriaturaRepositorio();
    $c    = $repo->buscarPorId($id);
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao consultar a criatura: ' . $e->getMessage());
    header('Location: ' . url('/criaturas/listar.php'));
    exit;
}

if ($c === null) {
    definirFlash('aviso', "Criatura #{$id} não foi localizada.");
    header('Location: ' . url('/criaturas/listar.php'));
    exit;
}

$elemSlug = strtolower((string) $c['elemento']);
$fotoUrl  = UploadHelper::urlImagem('criaturas', $c['foto_arquivo'] ?? null);

$campanhaNome = '';
if (!empty($c['campanha_id'])) {
    $campanha = (new CampanhaRepositorio())->buscarPorId((int) $c['campanha_id']);
    $campanhaNome = $campanha['nome'] ?? '';
}

$titulo      = 'CRIATURA_' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
$paginaAtiva = 'bestiario';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        AMEAÇA #<?= str_pad((string) $c['id'], 4, '0', STR_PAD_LEFT) ?>
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        ARQUIVO INDIVIDUAL // ELEMENTO:
        <strong><?= escapar(strtoupper((string) $c['elemento'])) ?></strong>
    </p>
    <div class="cabecalho-pagina__acoes">
        <a href="<?= escapar(url('/criaturas/formulario.php?id=' . (int) $c['id'])) ?>"
           class="botao botao--primario">EDITAR</a>
        <a href="<?= escapar(url('/criaturas/listar.php')) ?>" class="botao botao--secundario">VOLTAR</a>
        <form action="<?= escapar(url('/criaturas/excluir.php')) ?>" method="POST"
              data-confirmar="Confirma a expurgação da criatura <?= escapar($c['nome']) ?>?"
              class="formulario-inline">
            <input type="hidden" name="csrf_token" value="<?= escapar(gerarTokenCsrf()) ?>">
            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <button type="submit" class="botao botao--perigo">EXPURGAR</button>
        </form>
    </div>
</section>

<article class="dossie dossie--criatura dossie--elem-<?= escapar($elemSlug) ?>">
    <header class="dossie__cabecalho">
        <?php if ($fotoUrl): ?>
            <img src="<?= escapar($fotoUrl) ?>" alt="" class="dossie__foto">
        <?php else: ?>
            <div class="dossie__foto dossie__foto--vazia" aria-hidden="true">&#9678;</div>
        <?php endif; ?>
        <div class="dossie__identificacao">
            <span class="dossie__codigo">// AMEAÇA_PARANORMAL</span>
            <h2 class="dossie__nome"><?= escapar((string) $c['nome']) ?></h2>
            <?php if ($campanhaNome): ?>
                <span class="dossie__ocupacao">// <?= escapar($campanhaNome) ?></span>
            <?php endif; ?>
        </div>
        <div class="dossie__selo dossie__selo--elem-<?= escapar($elemSlug) ?>">
            <span class="dossie__selo-rotulo">ELEMENTO</span>
            <span class="dossie__selo-valor"><?= escapar(strtoupper((string) $c['elemento'])) ?></span>
        </div>
    </header>

    <dl class="dossie__metadados">
        <div class="dossie__campo">
            <dt>// VALOR DE DESAFIO</dt>
            <dd><?= escapar((string) (float) $c['vd']) ?></dd>
        </div>
        <div class="dossie__campo">
            <dt>// PONTOS DE VIDA</dt>
            <dd><?= (int) $c['pv_atual'] ?> / <?= (int) $c['pv_maximo'] ?></dd>
        </div>
        <div class="dossie__campo">
            <dt>// CATALOGADA EM</dt>
            <dd><?= escapar(substr((string) $c['criado_em'], 0, 16)) ?></dd>
        </div>
    </dl>

    <section class="dossie__historia">
        <h3 class="dossie__historia-titulo">// HABILIDADES E COMPORTAMENTO</h3>
        <div class="dossie__historia-corpo">
            <?= nl2br(escapar((string) $c['habilidades'])) ?>
        </div>
    </section>

    <footer class="dossie__rodape">
        <span>// FIM DO ARQUIVO</span>
        <span>// CLASSIFICADO PELA ORDEM</span>
    </footer>
</article>

<?php require __DIR__ . '/../views/rodape.php'; ?>
