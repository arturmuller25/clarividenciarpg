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

try {
    $totalCampanhas  = (new CampanhaRepositorio())->contar();
    $totalAgentes    = (new AgenteRepositorio())->contar();
    $totalNpcs       = (new NpcRepositorio())->contar();
    $totalCriaturas  = (new CriaturaRepositorio())->contar();
    $logRepo         = new LogRepositorio();
    $ultimasRolagens = $logRepo->listarRecentes(5);
    $totalRolagens   = $logRepo->contar();
} catch (Throwable $e) {
    definirFlash('aviso', 'Indicadores indisponiveis (banco offline?): ' . $e->getMessage());
}

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

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__bracket">[</span>
        PAINEL DO MESTRE
        <span class="cabecalho-pagina__bracket">]</span>
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        ACESSO AUTORIZADO. SELECIONE UM MODULO PARA INICIAR A INVESTIGACAO.
    </p>
</section>

<section class="indicadores" aria-label="Visao geral da sessao">
    <article class="indicador">
        <span class="indicador__rotulo">// CAMPANHAS_ATIVAS</span>
        <span class="indicador__valor"><?= str_pad((string) $totalCampanhas, 3, '0', STR_PAD_LEFT) ?></span>
        <a class="indicador__link" href="<?= escapar(url('/campanhas/listar.php')) ?>">CONSULTAR &rarr;</a>
    </article>
    <article class="indicador">
        <span class="indicador__rotulo">// AGENTES_ATIVOS</span>
        <span class="indicador__valor"><?= str_pad((string) $totalAgentes, 3, '0', STR_PAD_LEFT) ?></span>
        <a class="indicador__link" href="<?= escapar(url('/agentes/listar.php')) ?>">CONSULTAR &rarr;</a>
    </article>
    <article class="indicador">
        <span class="indicador__rotulo">// DOSSIÊS_NPC</span>
        <span class="indicador__valor"><?= str_pad((string) $totalNpcs, 3, '0', STR_PAD_LEFT) ?></span>
        <a class="indicador__link" href="<?= escapar(url('/npcs/listar.php')) ?>">CONSULTAR &rarr;</a>
    </article>
    <article class="indicador">
        <span class="indicador__rotulo">// AMEAÇAS_BESTIÁRIO</span>
        <span class="indicador__valor"><?= str_pad((string) $totalCriaturas, 3, '0', STR_PAD_LEFT) ?></span>
        <a class="indicador__link" href="<?= escapar(url('/criaturas/listar.php')) ?>">CONSULTAR &rarr;</a>
    </article>
    <article class="indicador">
        <span class="indicador__rotulo">// ROLAGENS_REGISTRADAS</span>
        <span class="indicador__valor"><?= str_pad((string) $totalRolagens, 3, '0', STR_PAD_LEFT) ?></span>
        <a class="indicador__link" href="<?= escapar(url('/historico/listar.php')) ?>">CONSULTAR &rarr;</a>
    </article>
</section>

<div class="painel-secundario">
    <section class="painel-secundario__bloco">
        <h2 class="painel-secundario__titulo">// MODULOS_DISPONIVEIS</h2>
        <div class="grade-modulos">
            <a href="<?= escapar(url('/campanhas/listar.php')) ?>" class="cartao-modulo cartao-modulo--ativo">
                <span class="cartao-modulo__codigo">// 01</span>
                <h3 class="cartao-modulo__nome">CAMPANHAS</h3>
                <p class="cartao-modulo__descricao">
                    Operações ativas: capa, sistema, agentes vinculados.
                </p>
                <span class="cartao-modulo__status">[OPERACIONAL]</span>
            </a>

            <a href="<?= escapar(url('/agentes/listar.php')) ?>" class="cartao-modulo cartao-modulo--ativo">
                <span class="cartao-modulo__codigo">// 02</span>
                <h3 class="cartao-modulo__nome">AGENTES</h3>
                <p class="cartao-modulo__descricao">
                    Fichas dos personagens jogadores (PJs). CRUD completo na próxima fase.
                </p>
                <span class="cartao-modulo__status">[LEITURA]</span>
            </a>

            <a href="<?= escapar(url('/npcs/listar.php')) ?>" class="cartao-modulo cartao-modulo--ativo">
                <span class="cartao-modulo__codigo">// 03</span>
                <h3 class="cartao-modulo__nome">NPCS</h3>
                <p class="cartao-modulo__descricao">
                    Cadastro e dossie de personagens nao-jogaveis.
                </p>
                <span class="cartao-modulo__status">[OPERACIONAL]</span>
            </a>

            <a href="<?= escapar(url('/criaturas/listar.php')) ?>" class="cartao-modulo cartao-modulo--ativo">
                <span class="cartao-modulo__codigo">// 04</span>
                <h3 class="cartao-modulo__nome">BESTIÁRIO</h3>
                <p class="cartao-modulo__descricao">
                    Catalogo de criaturas paranormais classificadas por elemento.
                </p>
                <span class="cartao-modulo__status">[OPERACIONAL]</span>
            </a>

            <a href="<?= escapar(url('/rolagem/index.php')) ?>" class="cartao-modulo cartao-modulo--ativo">
                <span class="cartao-modulo__codigo">// 05</span>
                <h3 class="cartao-modulo__nome">ROLAGEM</h3>
                <p class="cartao-modulo__descricao">
                    Lançador de dados d4..d100 com regra do maior valor.
                </p>
                <span class="cartao-modulo__status">[OPERACIONAL]</span>
            </a>

            <a href="<?= escapar(url('/historico/listar.php')) ?>" class="cartao-modulo cartao-modulo--ativo">
                <span class="cartao-modulo__codigo">// 06</span>
                <h3 class="cartao-modulo__nome">HISTÓRICO</h3>
                <p class="cartao-modulo__descricao">
                    Registro de todas as rolagens efetuadas no terminal.
                </p>
                <span class="cartao-modulo__status">[OPERACIONAL]</span>
            </a>
        </div>
    </section>

    <aside class="painel-secundario__bloco painel-secundario__bloco--lateral">
        <h2 class="painel-secundario__titulo">// ULTIMOS_REGISTROS</h2>
        <?php if (empty($ultimasRolagens)): ?>
            <p class="painel-secundario__vazio">
                Nenhuma rolagem registrada ainda.<br>
                <a href="<?= escapar(url('/rolagem/index.php')) ?>" class="link">[INVOCAR PRIMEIRA ROLAGEM]</a>
            </p>
        <?php else: ?>
            <ol class="lista-eventos">
                <?php foreach ($ultimasRolagens as $r):
                    $ehCritico  = (bool) $r['eh_critico'];
                    $ehDesastre = (bool) $r['eh_desastre'];
                    $classeRes  = $ehCritico ? 'is-critico' : ($ehDesastre ? 'is-desastre' : '');
                ?>
                    <li class="lista-eventos__item">
                        <span class="lista-eventos__quando">
                            <?= escapar(substr((string) $r['rolado_em'], 0, 16)) ?>
                        </span>
                        <span class="lista-eventos__motivo"
                              title="<?= escapar((string) $r['descricao']) ?>">
                            <strong><?= escapar((string) $r['quem_rolou']) ?></strong>
                            &mdash; <?= escapar(mb_strimwidth((string) $r['descricao'], 0, 48, '...')) ?>
                        </span>
                        <span class="lista-eventos__resultado <?= $classeRes ?>">
                            <?= (int) $r['resultado_final'] ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
            <a href="<?= escapar(url('/historico/listar.php')) ?>" class="painel-secundario__link">VER DIARIO COMPLETO &rarr;</a>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/views/rodape.php'; ?>
