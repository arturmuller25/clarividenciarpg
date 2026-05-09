<?php
declare(strict_types=1);

/**
 * Painel do Mestre - Dashboard com visão geral da sessão.
 * Mostra contagens dos arquivos, últimas rolagens registradas e atalhos.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/sessao.php';
require_once __DIR__ . '/src/NpcRepositorio.php';
require_once __DIR__ . '/src/CriaturaRepositorio.php';
require_once __DIR__ . '/src/LogRepositorio.php';
require_once __DIR__ . '/src/CampanhaRepositorio.php';
require_once __DIR__ . '/src/AgenteRepositorio.php';

iniciarSessao();

$totalCampanhas  = 0;
$totalAgentes    = 0;
$totalNpcs       = 0;
$totalCriaturas  = 0;
$totalRolagens   = 0;
$ultimasRolagens = [];
$ultimaCritica   = null;
$rolagens7d      = [0, 0, 0, 0, 0, 0, 0];
$ultimaCriatura  = null;
$ultimaCampanha  = null;

try {
    $totalCampanhas  = (new CampanhaRepositorio())->contar();
    $totalAgentes    = (new AgenteRepositorio())->contar();
    $totalNpcs       = (new NpcRepositorio())->contar();
    $totalCriaturas  = (new CriaturaRepositorio())->contar();
    $logRepo         = new LogRepositorio();
    $ultimasRolagens = $logRepo->listarRecentes(5);
    $totalRolagens   = $logRepo->contar();
    $ultimaCritica   = $logRepo->buscarUltimaCritica();
    $rolagens7d      = $logRepo->contarPorDia(7);

    $criaturasRecentes = (new CriaturaRepositorio())->listar();
    $ultimaCriatura    = $criaturasRecentes[0] ?? null;

    $campanhasRecentes = (new CampanhaRepositorio())->listar();
    $ultimaCampanha    = $campanhasRecentes[0] ?? null;
} catch (Throwable $e) {
    definirFlash('aviso', 'Indicadores indisponiveis (banco offline?): ' . $e->getMessage());
}

/* Sparkline 7d — gera polyline points "x,y x,y ..." normalizado em viewBox 70x24. */
$sparklineMax = max(max($rolagens7d), 1);
$sparklinePoints = '';
foreach ($rolagens7d as $i => $valor) {
    $x = (int) round(($i / 6) * 70);
    $y = (int) round(23 - ($valor / $sparklineMax) * 21);
    $sparklinePoints .= "{$x},{$y} ";
}
$sparklinePoints = trim($sparklinePoints);
$sparklineSoma   = array_sum($rolagens7d);

$titulo      = 'PAINEL_DO_MESTRE';
$paginaAtiva = 'inicio';
require __DIR__ . '/views/cabecalho.php';
?>

<!-- ============================================================
     HERO / SPLASH SCREEN — animacao 3D dinamica do icosaedro
     - Decisao 019: gate first-visit/F5 via hero.js::deveRodarHero()
     - SVG e <div class="particle"> gerados em runtime pelo hero.js
       (geometria 3D real: 12 vertices via phi, 20 faces, painter's
       algorithm, integer turns => paragem matematicamente exata na
       face 20 sem snap)
     - Audio: som_para_a_hero.mp3 acompanha queda+impacto (~3.5s);
       silencio narrativo durante o assentamento e hover
     - Timeline: queda 1.4s + bounce 0.3s + tumble decelerante ate
       4.5s + hover infinito; titulo aparece em 5.0s
     ============================================================ -->
<div class="hero" role="dialog" aria-label="Tela de boas-vindas"
     data-audio="<?= escapar(url('/assets/audio/som_para_a_hero.mp3')) ?>"
     data-duracao-ms="5200">
    <div class="hero__particles" id="hero-particles" aria-hidden="true"></div>
    <div class="hero__palco">
        <div class="hero__floor" aria-hidden="true"></div>
        <div class="hero__aura"></div>

        <!-- Ondas de choque concentricas no instante do impacto -->
        <span class="hero__shockwave hero__shockwave--1" aria-hidden="true"></span>
        <span class="hero__shockwave hero__shockwave--2" aria-hidden="true"></span>
        <span class="hero__shockwave hero__shockwave--flash" aria-hidden="true"></span>

        <!-- Container do dado 3D — JS injeta SVG dinamico aqui -->
        <div class="hero__d20-3d" id="hero-dice"></div>
    </div>

    <h1 class="hero__titulo">
        Clarivid&ecirc;ncia
        <span class="hero__titulo-realce">Paranormal</span>
    </h1>
    <p class="hero__subtitulo">// TERMINAL DA ORDEM // ACESSO LIBERADO</p>

    <!-- Fallback quando autoplay do audio for bloqueado -->
    <button type="button" class="hero__iniciar" hidden>
        &#9678; CLIQUE PARA INICIAR O RITUAL
    </button>
</div>

<!-- ===========================================================
     SECAO 1 — PAINEL-HERO (hero do dashboard, NAO confundir com Hero d20)
     - Mensagem de acesso autorizado + glitch sutil em "MESTRE"
     - Citação poética em Cormorant Garamond italic
     - Card lateral "ULTIMA ATIVIDADE CRITICA" — borda colorida por estado
     =========================================================== -->
<section class="painel-hero" aria-label="Painel do Mestre">
    <div class="painel-hero__esq">
        <span class="painel-hero__acesso">
            <span class="painel-hero__pulso" aria-hidden="true"></span>
            ACESSO AUTORIZADO &nbsp;//&nbsp; SESSAO ATIVA
        </span>
        <h1 class="painel-hero__h1">
            PAINEL <span class="gold">DO</span>
            MES<span class="glitch" data-glitch="T">T</span>RE
        </h1>
        <p class="painel-hero__sub">
            Acesso liberado. Selecione um modulo para iniciar a investigacao.
        </p>
        <span class="painel-hero__op">
            <span>OP. <b>ATIVA</b></span>
            <span>NIVEL <b>2</b></span>
            <span>// CLASSIFICADO</span>
        </span>
    </div>

    <article class="ultima-critica<?= $ultimaCritica
        ? ((bool) $ultimaCritica['eh_critico'] ? ' is-critico' : ' is-desastre')
        : '' ?>" aria-label="Ultima atividade critica">
        <header class="ultima-critica__cab">
            <span>// ULTIMA_ATIVIDADE_CRITICA</span>
            <span><?= $ultimaCritica
                ? escapar(substr((string) $ultimaCritica['rolado_em'], 0, 16))
                : '— —' ?></span>
        </header>

        <?php if ($ultimaCritica): ?>
            <span class="ultima-critica__quem">
                <?= escapar((string) $ultimaCritica['quem_rolou']) ?>
                <span style="opacity:.5"> // <?= escapar(strtoupper((string) $ultimaCritica['tipo_dado'])) ?></span>
            </span>
            <p class="ultima-critica__desc">
                "<?= escapar(mb_strimwidth((string) $ultimaCritica['descricao'], 0, 96, '...')) ?>"
            </p>
            <div class="ultima-critica__num">
                <?= (int) $ultimaCritica['resultado_final'] ?>
                <span class="ultima-critica__selo">
                    <?= ((bool) $ultimaCritica['eh_critico']) ? '◎ CRITICO ◎' : '▼ DESASTRE ▼' ?>
                </span>
            </div>
        <?php else: ?>
            <p class="ultima-critica__vazio">
                Nenhum sucesso decisivo nem desastre registrado ainda.<br>
                O proximo 20 natural marca o instante.
            </p>
        <?php endif; ?>
    </article>
</section>

<!-- ===========================================================
     SECAO 2 — FAIXA DE STATS (5 tiles + sparkline 7d)
     =========================================================== -->
<section class="faixa-stats" aria-label="Estatisticas da sessao">
    <article class="stat-tile">
        <span class="stat-tile__rotulo">// CAMPANHAS</span>
        <span class="stat-tile__valor"><?= str_pad((string) $totalCampanhas, 3, '0', STR_PAD_LEFT) ?></span>
        <span class="stat-tile__hint"><?= $totalCampanhas > 0
            ? '// EM_ANDAMENTO'
            : '// AGUARDANDO_PRIMEIRA' ?></span>
        <a class="stat-tile__link" href="<?= escapar(url('/campanhas/listar.php')) ?>">Consultar &rarr;</a>
    </article>
    <article class="stat-tile">
        <span class="stat-tile__rotulo">// AGENTES</span>
        <span class="stat-tile__valor"><?= str_pad((string) $totalAgentes, 3, '0', STR_PAD_LEFT) ?></span>
        <span class="stat-tile__hint"><?= $totalAgentes > 0
            ? '// EM_CAMPO'
            : '// SEM_INVESTIGADORES' ?></span>
        <a class="stat-tile__link" href="<?= escapar(url('/agentes/listar.php')) ?>">Consultar &rarr;</a>
    </article>
    <article class="stat-tile">
        <span class="stat-tile__rotulo">// NPCS</span>
        <span class="stat-tile__valor"><?= str_pad((string) $totalNpcs, 3, '0', STR_PAD_LEFT) ?></span>
        <span class="stat-tile__hint"><?= $totalNpcs > 0
            ? '// DOSSIES_ABERTOS'
            : '// SEM_CONTATOS' ?></span>
        <a class="stat-tile__link" href="<?= escapar(url('/npcs/listar.php')) ?>">Consultar &rarr;</a>
    </article>
    <article class="stat-tile">
        <span class="stat-tile__rotulo">// AMEACAS</span>
        <span class="stat-tile__valor"><?= str_pad((string) $totalCriaturas, 3, '0', STR_PAD_LEFT) ?></span>
        <span class="stat-tile__hint"><?= $totalCriaturas > 0
            ? '// CATALOGADAS'
            : '// BESTIARIO_LIMPO' ?></span>
        <a class="stat-tile__link" href="<?= escapar(url('/criaturas/listar.php')) ?>">Consultar &rarr;</a>
    </article>
    <article class="stat-tile stat-tile--sparkline">
        <span class="stat-tile__rotulo">// ROLAGENS_7D</span>
        <svg class="sparkline" viewBox="0 0 70 24" preserveAspectRatio="none" aria-hidden="true">
            <polyline points="<?= escapar($sparklinePoints) ?>"/>
        </svg>
        <span class="stat-tile__hint">SOMA 7D: <?= (int) $sparklineSoma ?> // TOTAL: <?= (int) $totalRolagens ?></span>
    </article>
</section>

<!-- ===========================================================
     SECAO 3 — TRILHAS DE INVESTIGACAO (3 principais + 3 auxiliares)
     =========================================================== -->
<section class="trilhas" aria-label="Trilhas de investigacao">
    <h2 class="trilhas__h2">// TRILHAS_DE_INVESTIGACAO</h2>

    <div class="trilhas__principais">
        <a href="<?= escapar(url('/campanhas/listar.php')) ?>" class="trilha-card">
            <span class="trilha-card__codigo">// 01 // OPERACIONAL</span>
            <h3 class="trilha-card__nome">Campanhas</h3>
            <p class="trilha-card__desc">
                Operacoes ativas: capa, sistema, agentes vinculados.
                O fio condutor da investigacao.
            </p>
            <span class="trilha-card__status">[OPERACIONAL]</span>
        </a>

        <a href="<?= escapar(url('/criaturas/listar.php')) ?>" class="trilha-card">
            <span class="trilha-card__codigo">// 04 // CLASSIFICADO</span>
            <h3 class="trilha-card__nome">Bestiario Paranormal</h3>
            <p class="trilha-card__desc">
                Catalogo de ameacas classificadas pelos 4 elementos do
                Outro Lado. Nao subestime nada.
            </p>
            <span class="trilha-card__status">[OPERACIONAL]</span>
        </a>

        <a href="<?= escapar(url('/rolagem/index.php')) ?>" class="trilha-card">
            <span class="trilha-card__codigo">// 05 // RITUAL</span>
            <h3 class="trilha-card__nome">Lancador de Dados</h3>
            <p class="trilha-card__desc">
                d4 a d100. Regra OP no d20 (vantagem/desastre). Audio
                em camadas conforme quantidade.
            </p>
            <span class="trilha-card__status">[OPERACIONAL]</span>
        </a>
    </div>

    <div class="trilhas__auxiliares">
        <a href="<?= escapar(url('/agentes/listar.php')) ?>" class="trilha-card trilha-card--secundario">
            <span class="trilha-card__codigo">// 02</span>
            <h3 class="trilha-card__nome">Agentes</h3>
            <p class="trilha-card__desc">Fichas completas dos PJs.</p>
        </a>
        <a href="<?= escapar(url('/npcs/listar.php')) ?>" class="trilha-card trilha-card--secundario">
            <span class="trilha-card__codigo">// 03</span>
            <h3 class="trilha-card__nome">NPCs</h3>
            <p class="trilha-card__desc">Dossies de personagens nao-jogaveis.</p>
        </a>
        <a href="<?= escapar(url('/historico/listar.php')) ?>" class="trilha-card trilha-card--secundario">
            <span class="trilha-card__codigo">// 06</span>
            <h3 class="trilha-card__nome">Diario de Missao</h3>
            <p class="trilha-card__desc">Cronologia de todas as rolagens.</p>
        </a>
    </div>
</section>

<!-- ===========================================================
     SECAO 4 — SUSSURROS DO OUTRO LADO (atmosférico)
     - Cita ultima criatura, ultima campanha e ultima atividade narrativa
     - Tipografia em Cormorant Garamond / IM Fell English (Decisao D5)
     =========================================================== -->
<section class="sussurros" aria-label="Sussurros do Outro Lado">
    <h2 class="sussurros__h2">// SUSSURROS_DO_OUTRO_LADO</h2>
    <p class="sussurros__poetico">Os véus estão tênues. O Outro Lado escuta.</p>

    <div class="sussurros__grid">
        <article class="sussurros__card">
            <span class="sussurros__rotulo">ULTIMA AMEACA CATALOGADA</span>
            <?php if ($ultimaCriatura): ?>
                <span class="sussurros__nome"><?= escapar((string) $ultimaCriatura['nome']) ?></span>
                <p class="sussurros__corpo">
                    Elemento <?= escapar((string) $ultimaCriatura['elemento']) ?>.
                    Catalogada em <?= escapar(substr((string) ($ultimaCriatura['criado_em'] ?? ''), 0, 10)) ?>.
                </p>
            <?php else: ?>
                <span class="sussurros__nome">— silencio —</span>
                <p class="sussurros__corpo">Nenhuma criatura registrada. O Bestiario aguarda.</p>
            <?php endif; ?>
        </article>

        <article class="sussurros__card">
            <span class="sussurros__rotulo">CAMPANHA EM ANDAMENTO</span>
            <?php if ($ultimaCampanha): ?>
                <span class="sussurros__nome"><?= escapar((string) $ultimaCampanha['nome']) ?></span>
                <p class="sussurros__corpo">
                    Sistema: <?= escapar((string) ($ultimaCampanha['sistema'] ?? 'Ordem Paranormal')) ?>.
                    Os agentes estao em campo.
                </p>
            <?php else: ?>
                <span class="sussurros__nome">— sem ordem ativa —</span>
                <p class="sussurros__corpo">Nenhuma campanha registrada. Inicie a primeira.</p>
            <?php endif; ?>
        </article>

        <article class="sussurros__card">
            <span class="sussurros__rotulo">PROXIMA INVOCACAO</span>
            <span class="sussurros__nome">Ritual pendente</span>
            <p class="sussurros__corpo">
                <?= $totalRolagens > 0
                    ? 'O ultimo eco do d20 ainda reverbera. Continue o ritual.'
                    : 'Nenhum dado lancado ainda. O primeiro 20 anuncia o despertar.' ?>
            </p>
        </article>
    </div>
</section>

<!-- ===========================================================
     SECAO 5 — ATALHOS RAPIDOS (rodape)
     =========================================================== -->
<section class="atalhos-rapidos" aria-label="Acesso direto">
    <h2 class="atalhos-rapidos__h2">// ACESSO_DIRETO</h2>
    <div class="atalhos-rapidos__grade">
        <a class="atalho-rapido" href="<?= escapar(url('/campanhas/formulario.php')) ?>">
            <span class="atalho-rapido__plus">+</span>NOVA CAMPANHA
        </a>
        <a class="atalho-rapido" href="<?= escapar(url('/agentes/formulario.php')) ?>">
            <span class="atalho-rapido__plus">+</span>NOVO AGENTE
        </a>
        <a class="atalho-rapido" href="<?= escapar(url('/criaturas/formulario.php')) ?>">
            <span class="atalho-rapido__plus">+</span>NOVA AMEACA
        </a>
        <a class="atalho-rapido" href="<?= escapar(url('/rolagem/index.php')) ?>">
            <span class="atalho-rapido__plus">&#9678;</span>INVOCAR ROLAGEM
        </a>
    </div>
</section>

<?php require __DIR__ . '/views/rodape.php'; ?>
