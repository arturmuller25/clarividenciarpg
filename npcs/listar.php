<?php
declare(strict_types=1);

/**
 * Galeria de NPCs cadastrados, com filtros por atitude e localização.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/NpcRepositorio.php';

iniciarSessao();

$filtroAtitude     = isset($_GET['atitude'])     ? (string) $_GET['atitude']     : '';
$filtroLocalizacao = isset($_GET['localizacao']) ? (string) $_GET['localizacao'] : '';

try {
    $repo = new NpcRepositorio();
    $npcs = $repo->listar([
        'atitude'     => $filtroAtitude,
        'localizacao' => $filtroLocalizacao,
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
        DOSSIES DE NPCS
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        TOTAL DE FICHAS LOCALIZADAS: <strong><?= count($npcs) ?></strong>
    </p>
    <div class="cabecalho-pagina__acoes">
        <a href="/npcs/formulario.php" class="botao botao--primario">
            <span aria-hidden="true">+</span> NOVO NPC
        </a>
        <a href="/index.php" class="botao botao--secundario">VOLTAR AO PAINEL</a>
    </div>
</section>

<form method="GET" class="filtros" aria-label="Filtros de NPCs">
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
        <label for="filtro-localizacao" class="filtros__rotulo">// LOCALIZACAO</label>
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
        <a href="/npcs/listar.php" class="botao botao--pequeno botao--secundario">LIMPAR</a>
    </div>
</form>

<?php if (empty($npcs)): ?>
    <div class="estado-vazio">
        <pre class="estado-vazio__arte" aria-hidden="true">
[ NENHUM_DOSSIE_LOCALIZADO ]

         .--.
        /    \
       | () () |
        \  ^  /
         |||||
         |||||
        </pre>
        <p class="estado-vazio__texto">
            Nenhum NPC corresponde aos filtros aplicados. Ajuste os filtros ou
            <a href="/npcs/formulario.php" class="link">[REGISTRE UM NOVO DOSSIE]</a>.
        </p>
    </div>
<?php else: ?>
    <div class="galeria" role="list">
        <?php foreach ($npcs as $npc):
            $atitudeSlug = strtolower((string) $npc['atitude']);
        ?>
            <article class="cartao-npc cartao-npc--<?= escapar($atitudeSlug) ?>" role="listitem">
                <header class="cartao-npc__topo">
                    <span class="cartao-npc__id">#<?= str_pad((string) $npc['id'], 4, '0', STR_PAD_LEFT) ?></span>
                    <span class="cartao-npc__atitude cartao-npc__atitude--<?= escapar($atitudeSlug) ?>">
                        <?= escapar(strtoupper((string) $npc['atitude'])) ?>
                    </span>
                </header>

                <h2 class="cartao-npc__nome"><?= escapar($npc['nome']) ?></h2>

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
                    <a href="/npcs/formulario.php?id=<?= (int) $npc['id'] ?>"
                       class="botao botao--pequeno">EDITAR</a>
                    <form action="/npcs/excluir.php" method="POST"
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
