<?php
declare(strict_types=1);

/**
 * Galeria de NPCs cadastrados, com filtros por atitude e localização.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/NpcRepositorio.php';
require_once __DIR__ . '/../src/UploadHelper.php';

iniciarSessao();

$filtroAtitude     = isset($_GET['atitude'])     ? (string) $_GET['atitude']     : '';
$filtroLocalizacao = isset($_GET['localizacao']) ? (string) $_GET['localizacao'] : '';
$filtroBusca       = isset($_GET['busca'])       ? trim((string) $_GET['busca']) : '';

try {
    $repo = new NpcRepositorio();
    $npcs = $repo->listar([
        'atitude'     => $filtroAtitude,
        'localizacao' => $filtroLocalizacao,
        'busca'       => $filtroBusca,
    ]);
    $localizacoes = $repo->localizacoesDistintas();
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao consultar NPCs: ' . $e->getMessage());
    $npcs         = [];
    $localizacoes = [];
}

$titulo      = 'NPCS_LISTADOS';
$paginaAtiva = 'npcs';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        DOSSIÊS DE NPCS
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        TOTAL DE FICHAS LOCALIZADAS: <strong><?= count($npcs) ?></strong>
        — DOSSIÊS DA ORDEM
    </p>
    <div class="cabecalho-pagina__acoes">
        <a href="<?= escapar(url('/npcs/formulario.php')) ?>" class="botao botao--primario">
            <span aria-hidden="true">+</span> NOVO NPC
        </a>
        <a href="<?= escapar(url('/index.php')) ?>" class="botao botao--secundario">VOLTAR AO PAINEL</a>
    </div>
</section>

<form method="GET" class="filtros" aria-label="Filtros de NPCs">
    <div class="filtros__grupo filtros__grupo--busca">
        <label for="filtro-busca" class="filtros__rotulo">// BUSCAR DOSSIÊ</label>
        <input type="search" id="filtro-busca" name="busca"
               class="filtros__entrada"
               value="<?= escapar($filtroBusca) ?>"
               maxlength="120"
               placeholder="nome, ocupacao ou trecho da historia..."
               autocomplete="off">
    </div>

    <div class="filtros__grupo">
        <label for="filtro-atitude" class="filtros__rotulo">// ATITUDE</label>
        <select id="filtro-atitude" name="atitude" class="filtros__select">
            <option value="">[ TODAS ]</option>
            <?php foreach (NpcRepositorio::ATITUDES as $opcao): ?>
                <option value="<?= escapar($opcao) ?>"
                    <?= $filtroAtitude === $opcao ? 'selected' : '' ?>>
                    <?= escapar(strtoupper($opcao)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filtros__grupo">
        <label for="filtro-localizacao" class="filtros__rotulo">// LOCALIZAÇÃO</label>
        <select id="filtro-localizacao" name="localizacao" class="filtros__select">
            <option value="">[ TODAS ]</option>
            <?php foreach ($localizacoes as $loc): ?>
                <option value="<?= escapar($loc) ?>"
                    <?= $filtroLocalizacao === $loc ? 'selected' : '' ?>>
                    <?= escapar($loc) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filtros__acoes">
        <button type="submit" class="botao botao--pequeno">APLICAR</button>
        <a href="<?= escapar(url('/npcs/listar.php')) ?>" class="botao botao--pequeno botao--secundario">LIMPAR</a>
    </div>
</form>

<?php if (empty($npcs)): ?>
    <div class="estado-vazio">
        <pre class="estado-vazio__arte" aria-hidden="true">
[ NENHUM_DOSSIÊ_LOCALIZADO ]

         .--.
        /    \
       | () () |
        \  ^  /
         |||||
         |||||
        </pre>
        <p class="estado-vazio__texto">
            Nenhum NPC corresponde aos filtros aplicados. Ajuste os filtros ou
            <a href="<?= escapar(url('/npcs/formulario.php')) ?>" class="link">[REGISTRE UM NOVO DOSSIÊ]</a>.
        </p>
    </div>
<?php else: ?>
    <div class="galeria" role="list">
        <?php foreach ($npcs as $npc):
            $atitudeSlug = strtolower((string) $npc['atitude']);
            $fotoUrl     = UploadHelper::urlImagem('npcs', $npc['foto_arquivo'] ?? null);
        ?>
            <article class="cartao-npc cartao-npc--<?= escapar($atitudeSlug) ?>" role="listitem">
                <header class="cartao-npc__topo">
                    <span class="cartao-npc__id">#<?= str_pad((string) $npc['id'], 4, '0', STR_PAD_LEFT) ?></span>
                    <span class="cartao-npc__atitude cartao-npc__atitude--<?= escapar($atitudeSlug) ?>">
                        <?= escapar(strtoupper((string) $npc['atitude'])) ?>
                    </span>
                </header>

                <?php if ($fotoUrl): ?>
                    <img src="<?= escapar($fotoUrl) ?>" alt="" class="cartao-npc__foto">
                <?php endif; ?>

                <h2 class="cartao-npc__nome">
                    <a href="<?= escapar(url('/npcs/visualizar.php?id=' . (int) $npc['id'])) ?>"
                       class="cartao-npc__nome-link">
                        <?= escapar($npc['nome']) ?>
                    </a>
                </h2>

                <dl class="cartao-npc__meta">
                    <div class="cartao-npc__meta-linha">
                        <dt>OCUPACAO</dt>
                        <dd><?= escapar($npc['ocupacao']) ?></dd>
                    </div>
                    <div class="cartao-npc__meta-linha">
                        <dt>LOCAL</dt>
                        <dd><?= escapar($npc['localizacao']) ?></dd>
                    </div>
                </dl>

                <p class="cartao-npc__historia">
                    <?= escapar(mb_strimwidth((string) $npc['historia'], 0, 180, '...')) ?>
                </p>

                <footer class="cartao-npc__acoes">
                    <a href="<?= escapar(url('/npcs/visualizar.php?id=' . (int) $npc['id'])) ?>"
                       class="botao botao--pequeno botao--primario">DOSSIÊ</a>
                    <a href="<?= escapar(url('/npcs/formulario.php?id=' . (int) $npc['id'])) ?>"
                       class="botao botao--pequeno">EDITAR</a>
                    <form action="<?= escapar(url('/npcs/excluir.php')) ?>" method="POST"
                          data-confirmar="Confirma a exclusao do NPC <?= escapar($npc['nome']) ?>?"
                          class="formulario-inline">
                        <input type="hidden" name="csrf_token" value="<?= escapar(gerarTokenCsrf()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $npc['id'] ?>">
                        <button type="submit" class="botao botao--pequeno botao--perigo">ARQUIVAR</button>
                    </form>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../views/rodape.php'; ?>
