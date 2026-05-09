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
     HERO / SPLASH SCREEN
     - Roda em TODA recarga (F5) — sem cache de sessao.
     - JS tenta autoplay do som; se bloqueado, mostra botao
       "Iniciar Ritual" para o usuario liberar audio com 1 clique.
     - d20 SVG gira ferozmente por 1.8s, assenta com a face 20.
     - Titulo "Clarividencia Paranormal" desliza da direita com
       opacity 0->1, blur->nitido, ease-in-out (fantasmagorico).
     - Hero faz fadeOut suave apos ~4.4s revelando o Painel.
     ============================================================ -->
<div class="hero" role="dialog" aria-label="Tela de boas-vindas"
     data-audio="<?= escapar(url('/assets/audio/som_para_a_hero.mp3')) ?>"
     data-duracao-ms="4400">
    <div class="hero__palco">
        <div class="hero__aura"></div>

        <!-- Ondas de choque concêntricas: disparam no instante em que o
             d20 assenta no número 20, expandem e somem -->
        <span class="hero__shockwave hero__shockwave--1" aria-hidden="true"></span>
        <span class="hero__shockwave hero__shockwave--2" aria-hidden="true"></span>
        <span class="hero__shockwave hero__shockwave--flash" aria-hidden="true"></span>

        <!-- Wrapper 3D com perspective. O <svg> abaixo gira em rotateX/Y/Z
             dando a ilusão de um icosaedro caindo na mesa.
             Geometria: vista "face-on" do icosaedro, com 11 faces visíveis
             (1 central + 5 ao redor + 5 do anel equatorial). Cada face tem
             gradiente próprio (claro/médio/escuro) para sugerir iluminação. -->
        <div class="hero__d20-3d">
            <svg class="hero__d20" viewBox="0 0 200 200" aria-hidden="true">
                <defs>
                    <!-- Face frontal — mais clara (luz direta) -->
                    <linearGradient id="hero-face-claro" x1="20%" y1="0%" x2="80%" y2="100%">
                        <stop offset="0%"   stop-color="#3a3a3a"/>
                        <stop offset="100%" stop-color="#1a1a1a"/>
                    </linearGradient>
                    <!-- Faces médias — anel equatorial -->
                    <linearGradient id="hero-face-medio" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%"   stop-color="#1f1f1f"/>
                        <stop offset="100%" stop-color="#0a0a0a"/>
                    </linearGradient>
                    <!-- Faces traseiras — mais escuras (sombra) -->
                    <linearGradient id="hero-face-escuro" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%"   stop-color="#0c0c0c"/>
                        <stop offset="100%" stop-color="#000"/>
                    </linearGradient>
                    <!-- Highlight especular -->
                    <linearGradient id="hero-spec" x1="0%" y1="0%" x2="60%" y2="60%">
                        <stop offset="0%"   stop-color="rgba(255,255,255,0.20)"/>
                        <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
                    </linearGradient>
                </defs>

                <!-- Silhueta hexagonal externa (contorno do icosaedro vertice-on) -->
                <polygon class="hero__d20-borda"
                         points="100,8 188,55 188,145 100,192 12,145 12,55"/>

                <!-- Anel equatorial: 5 triangulos do "meio" do d20 (frontais) -->
                <!-- Cima-direita -->
                <polygon class="hero__d20-face hero__d20-face--escuro"
                         points="100,8 188,55 100,55"/>
                <!-- Direita-superior -->
                <polygon class="hero__d20-face hero__d20-face--medio"
                         points="188,55 188,100 100,55"/>
                <!-- Direita-inferior -->
                <polygon class="hero__d20-face hero__d20-face--medio"
                         points="188,100 188,145 100,145"/>
                <!-- Baixo-direita -->
                <polygon class="hero__d20-face hero__d20-face--escuro"
                         points="188,145 100,192 100,145"/>
                <!-- Baixo-esquerda -->
                <polygon class="hero__d20-face hero__d20-face--escuro"
                         points="100,192 12,145 100,145"/>
                <!-- Esquerda-inferior -->
                <polygon class="hero__d20-face hero__d20-face--medio"
                         points="12,145 12,100 100,145"/>
                <!-- Esquerda-superior -->
                <polygon class="hero__d20-face hero__d20-face--medio"
                         points="12,100 12,55 100,55"/>
                <!-- Cima-esquerda -->
                <polygon class="hero__d20-face hero__d20-face--escuro"
                         points="12,55 100,8 100,55"/>

                <!-- Faces centrais (a "frente" mais iluminada) -->
                <polygon class="hero__d20-face hero__d20-face--claro"
                         points="100,55 188,100 100,100"/>
                <polygon class="hero__d20-face hero__d20-face--claro"
                         points="100,55 100,100 12,100"/>
                <polygon class="hero__d20-face hero__d20-face--claro"
                         points="100,100 188,100 100,145"/>
                <polygon class="hero__d20-face hero__d20-face--claro"
                         points="100,100 100,145 12,100"/>

                <!-- Linhas de aresta dourada — finalizam a estrutura -->
                <g class="hero__d20-arestas">
                    <line x1="100" y1="8"   x2="100" y2="192"/>
                    <line x1="12"  y1="55"  x2="188" y2="55"/>
                    <line x1="12"  y1="100" x2="188" y2="100"/>
                    <line x1="12"  y1="145" x2="188" y2="145"/>
                    <line x1="100" y1="55"  x2="12"  y2="100"/>
                    <line x1="100" y1="55"  x2="188" y2="100"/>
                    <line x1="100" y1="145" x2="12"  y2="100"/>
                    <line x1="100" y1="145" x2="188" y2="100"/>
                </g>

                <!-- Camada de luz especular sobre a face superior -->
                <polygon class="hero__d20-spec" points="100,8 188,55 100,100 12,55"/>
            </svg>

            <span class="hero__face hero__face-7">7</span>
            <span class="hero__face hero__face-13">13</span>
            <span class="hero__face hero__face-2">2</span>
            <span class="hero__face hero__face-18">18</span>
            <span class="hero__face hero__face-5">5</span>
            <span class="hero__face hero__face-11">11</span>
            <span class="hero__face hero__face-9">9</span>
            <span class="hero__face hero__face-16">16</span>
            <span class="hero__face hero__face-3">3</span>
            <span class="hero__face hero__face-14">14</span>
            <span class="hero__face hero__face-8">8</span>
            <span class="hero__face hero__face-19">19</span>
            <span class="hero__face hero__face-20">20</span>
        </div>
    </div>

    <h1 class="hero__titulo">
        Clarivid&ecirc;ncia
        <span class="hero__titulo-realce">Paranormal</span>
    </h1>
    <p class="hero__subtitulo">// TERMINAL DA ORDEM // ACESSO LIBERADO</p>

    <!-- Mostrado SOMENTE se o autoplay do audio for bloqueado pelo navegador -->
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
        <a class="stat-tile__link" href="<?= escapar(url('/campanhas/listar.php')) ?>">Consultar &rarr;</a>
    </article>
    <article class="stat-tile">
        <span class="stat-tile__rotulo">// AGENTES</span>
        <span class="stat-tile__valor"><?= str_pad((string) $totalAgentes, 3, '0', STR_PAD_LEFT) ?></span>
        <a class="stat-tile__link" href="<?= escapar(url('/agentes/listar.php')) ?>">Consultar &rarr;</a>
    </article>
    <article class="stat-tile">
        <span class="stat-tile__rotulo">// NPCS</span>
        <span class="stat-tile__valor"><?= str_pad((string) $totalNpcs, 3, '0', STR_PAD_LEFT) ?></span>
        <a class="stat-tile__link" href="<?= escapar(url('/npcs/listar.php')) ?>">Consultar &rarr;</a>
    </article>
    <article class="stat-tile">
        <span class="stat-tile__rotulo">// AMEACAS</span>
        <span class="stat-tile__valor"><?= str_pad((string) $totalCriaturas, 3, '0', STR_PAD_LEFT) ?></span>
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

<aside class="microcopy-oculto" aria-hidden="true">// nao confiar nos numeros pares</aside>

<?php require __DIR__ . '/views/rodape.php'; ?>
