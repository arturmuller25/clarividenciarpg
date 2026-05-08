<?php
declare(strict_types=1);

/**
 * Dossiê individual de NPC. Ficha completa para consulta durante a sessão.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/NpcRepositorio.php';

iniciarSessao();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    definirFlash('erro', 'Identificador de NPC invalido.');
    header('Location: ' . url('/npcs/listar.php'));
    exit;
}

try {
    $repo = new NpcRepositorio();
    $npc  = $repo->buscarPorId($id);
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao consultar o dossie: ' . $e->getMessage());
    header('Location: ' . url('/npcs/listar.php'));
    exit;
}

if ($npc === null) {
    definirFlash('aviso', "Dossie #{$id} nao foi localizado nos arquivos da Ordem.");
    header('Location: ' . url('/npcs/listar.php'));
    exit;
}

$atitudeSlug  = strtolower((string) $npc['atitude']);
$atitudeRotulo = match ($atitudeSlug) {
    'amigavel' => 'CONTATO ALIADO',
    'hostil'   => 'AMEACA HOSTIL',
    default    => 'CONTATO NEUTRO',
};

$titulo      = 'DOSSIE_' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
$paginaAtiva = 'npcs';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        DOSSIE #<?= str_pad((string) $npc['id'], 4, '0', STR_PAD_LEFT) ?>
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        ARQUIVO INDIVIDUAL // CLASSIFICACAO:
        <strong><?= escapar($atitudeRotulo) ?></strong>
    </p>
    <div class="cabecalho-pagina__acoes">
        <a href="<?= escapar(url('/npcs/formulario.php?id=' . (int) $npc['id'])) ?>"
           class="botao botao--primario">EDITAR DOSSIE</a>
        <a href="<?= escapar(url('/npcs/listar.php')) ?>" class="botao botao--secundario">VOLTAR A GALERIA</a>
        <form action="<?= escapar(url('/npcs/excluir.php')) ?>" method="POST"
              data-confirmar="Confirma o arquivamento permanente de <?= escapar($npc['nome']) ?>?"
              class="formulario-inline">
            <input type="hidden" name="csrf_token" value="<?= escapar(gerarTokenCsrf()) ?>">
            <input type="hidden" name="id" value="<?= (int) $npc['id'] ?>">
            <button type="submit" class="botao botao--perigo">ARQUIVAR</button>
        </form>
    </div>
</section>

<article class="dossie dossie--<?= escapar($atitudeSlug) ?>">
    <header class="dossie__cabecalho">
        <div class="dossie__identificacao">
            <span class="dossie__codigo">// REGISTRO_NPC</span>
            <h2 class="dossie__nome"><?= escapar($npc['nome']) ?></h2>
            <span class="dossie__ocupacao"><?= escapar($npc['ocupacao']) ?></span>
        </div>
        <div class="dossie__selo dossie__selo--<?= escapar($atitudeSlug) ?>">
            <span class="dossie__selo-rotulo">ATITUDE</span>
            <span class="dossie__selo-valor"><?= escapar(strtoupper((string) $npc['atitude'])) ?></span>
        </div>
    </header>

    <dl class="dossie__metadados">
        <div class="dossie__campo">
            <dt>// LOCALIZACAO CONHECIDA</dt>
            <dd><?= escapar((string) $npc['localizacao']) ?></dd>
        </div>
        <div class="dossie__campo">
            <dt>// PRIMEIRO REGISTRO</dt>
            <dd><?= escapar(substr((string) $npc['criado_em'], 0, 16)) ?></dd>
        </div>
        <div class="dossie__campo">
            <dt>// ULTIMA ATUALIZACAO</dt>
            <dd><?= escapar(substr((string) $npc['atualizado_em'], 0, 16)) ?></dd>
        </div>
    </dl>

    <section class="dossie__historia">
        <h3 class="dossie__historia-titulo">// REGISTRO_DE_INVESTIGACAO</h3>
        <div class="dossie__historia-corpo">
            <?= nl2br(escapar((string) $npc['historia'])) ?>
        </div>
    </section>

    <footer class="dossie__rodape">
        <span>// FIM DO DOSSIE</span>
        <span>// CLASSIFICADO PELA ORDEM</span>
    </footer>
</article>

<?php require __DIR__ . '/../views/rodape.php'; ?>
