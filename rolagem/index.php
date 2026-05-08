<?php
declare(strict_types=1);

/**
 * Tela do Lançador de Dados (Ritual de Clarividência).
 *
 * A interação acontece em JS (assets/js/dados.js) e o resultado é
 * persistido via /rolagem/api.php.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';

iniciarSessao();

$titulo      = 'ROLAGEM_RITUAL';
$paginaAtiva = 'rolagem';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        RITUAL DE CLARIVIDENCIA
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        Lance N d20 e mantenha o MAIOR. Atributo zero invoca DESASTRE: rola dois e mantem o MENOR.
    </p>
</section>

<div class="rolador">
    <form id="form-rolagem"
          class="rolador__painel"
          data-csrf="<?= escapar(gerarTokenCsrf()) ?>"
          data-api="<?= escapar(url('/rolagem/api.php')) ?>"
          data-audio-rolagem="<?= escapar(url('/assets/audio/som_para_as_rolagens.mp3')) ?>"
          autocomplete="off">

        <div class="campo">
            <label for="quem_rolou" class="campo__rotulo">
                <span class="campo__indice">01.</span> QUEM ROLA
                <span class="campo__obrigatorio" aria-hidden="true">*</span>
            </label>
            <input type="text" id="quem_rolou" name="quem_rolou"
                   class="campo__entrada" maxlength="120" required
                   placeholder="Nome do investigador ou Mestre">
        </div>

        <div class="campo">
            <label for="descricao" class="campo__rotulo">
                <span class="campo__indice">02.</span> MOTIVO DA ROLAGEM
                <span class="campo__obrigatorio" aria-hidden="true">*</span>
            </label>
            <input type="text" id="descricao" name="descricao"
                   class="campo__entrada" maxlength="160" required
                   placeholder="Ex: Percepcao no porao do hospital">
        </div>

        <div class="campo">
            <label class="campo__rotulo">
                <span class="campo__indice">03.</span> TIPO DE DADO
                <span class="campo__obrigatorio" aria-hidden="true">*</span>
            </label>

            <!-- Sprite SVG: cada <symbol> e a projeção 2D canônica do poliedro.
                 Reusada via <use href="#geo-dN"/> em cada radio button. -->
            <svg width="0" height="0" style="position:absolute" aria-hidden="true">
                <defs>
                    <!-- d4: pirâmide (triângulo) -->
                    <symbol id="geo-d4" viewBox="0 0 80 80">
                        <polygon points="40,10 72,68 8,68" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <line x1="40" y1="10" x2="40" y2="68" stroke="currentColor" stroke-width="1" opacity="0.35"/>
                    </symbol>
                    <!-- d6: cubo (quadrado com bevel sutil) -->
                    <symbol id="geo-d6" viewBox="0 0 80 80">
                        <rect x="14" y="14" width="52" height="52" rx="3" fill="none" stroke="currentColor" stroke-width="2"/>
                        <line x1="14" y1="14" x2="22" y2="6" stroke="currentColor" stroke-width="1" opacity="0.35"/>
                        <line x1="66" y1="14" x2="58" y2="6" stroke="currentColor" stroke-width="1" opacity="0.35"/>
                        <line x1="22" y1="6"  x2="58" y2="6" stroke="currentColor" stroke-width="1" opacity="0.35"/>
                    </symbol>
                    <!-- d8: octaedro (losango) -->
                    <symbol id="geo-d8" viewBox="0 0 80 80">
                        <polygon points="40,8 72,40 40,72 8,40" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <line x1="8"  y1="40" x2="72" y2="40" stroke="currentColor" stroke-width="1" opacity="0.35"/>
                        <line x1="40" y1="8"  x2="40" y2="72" stroke="currentColor" stroke-width="1" opacity="0.35"/>
                    </symbol>
                    <!-- d10: pentágono duplo (kite vertical com divisor) -->
                    <symbol id="geo-d10" viewBox="0 0 80 80">
                        <polygon points="40,8 68,32 56,72 24,72 12,32" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <line x1="12" y1="32" x2="68" y2="32" stroke="currentColor" stroke-width="1" opacity="0.35"/>
                        <line x1="40" y1="8"  x2="40" y2="32" stroke="currentColor" stroke-width="1" opacity="0.35"/>
                    </symbol>
                    <!-- d12: dodecaedro (pentágono regular com pentágono interno) -->
                    <symbol id="geo-d12" viewBox="0 0 80 80">
                        <polygon points="40,6 70,30 58,68 22,68 10,30" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <polygon points="40,28 56,40 50,58 30,58 24,40" fill="none" stroke="currentColor" stroke-width="1" opacity="0.4"/>
                    </symbol>
                    <!-- d20: icosaedro (hexágono com triângulos internos) -->
                    <symbol id="geo-d20" viewBox="0 0 80 80">
                        <polygon points="40,6 70,24 70,56 40,74 10,56 10,24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <line x1="40" y1="6"  x2="40" y2="74" stroke="currentColor" stroke-width="1" opacity="0.4"/>
                        <line x1="10" y1="24" x2="70" y2="56" stroke="currentColor" stroke-width="1" opacity="0.4"/>
                        <line x1="70" y1="24" x2="10" y2="56" stroke="currentColor" stroke-width="1" opacity="0.4"/>
                    </symbol>
                    <!-- d100: esfera facetada -->
                    <symbol id="geo-d100" viewBox="0 0 80 80">
                        <circle cx="40" cy="40" r="32" fill="none" stroke="currentColor" stroke-width="2"/>
                        <ellipse cx="40" cy="40" rx="32" ry="14" fill="none" stroke="currentColor" stroke-width="1" opacity="0.4"/>
                        <ellipse cx="40" cy="40" rx="14" ry="32" fill="none" stroke="currentColor" stroke-width="1" opacity="0.4"/>
                        <line x1="8"  y1="40" x2="72" y2="40" stroke="currentColor" stroke-width="1" opacity="0.25"/>
                        <line x1="40" y1="8"  x2="40" y2="72" stroke="currentColor" stroke-width="1" opacity="0.25"/>
                    </symbol>
                </defs>
            </svg>

            <div class="seletor-dado" role="radiogroup" aria-label="Tipo de dado">
                <?php foreach (['d4','d6','d8','d10','d12','d20','d100'] as $tipo): ?>
                    <label class="seletor-dado__opcao seletor-dado__opcao--<?= $tipo ?>">
                        <input type="radio" name="tipo_dado" value="<?= $tipo ?>"
                               <?= $tipo === 'd20' ? 'checked' : '' ?>>
                        <span class="seletor-dado__forma" aria-hidden="true">
                            <svg class="seletor-dado__svg" viewBox="0 0 80 80" focusable="false">
                                <use href="#geo-<?= $tipo ?>"/>
                                <text x="40" y="48" text-anchor="middle"
                                      class="seletor-dado__num"
                                      font-family="JetBrains Mono, monospace"
                                      font-size="17" font-weight="700"
                                      fill="currentColor"><?= strtoupper($tipo) ?></text>
                            </svg>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <small class="campo__ajuda">
                d20 segue regra de Ordem Paranormal (rola N, pega o maior). Demais tipos rolam 1 dado simples.
            </small>
        </div>

        <div class="campo" id="campo-atributo">
            <label for="quantidade" class="campo__rotulo">
                <span class="campo__indice">04.</span> ATRIBUTO (N&Uacute;MERO DE DADOS)
            </label>
            <input type="number" id="quantidade" name="quantidade"
                   class="campo__entrada" min="0" max="10" value="3" required>
            <small class="campo__ajuda">
                0 = DESASTRE (rola 2, pega menor). 1-10 = vantagem (rola N, pega maior).
                <strong>Exclusivo do d20.</strong>
            </small>
        </div>

        <div class="formulario__acoes">
            <button type="submit" class="botao botao--primario">INVOCAR ROLAGEM</button>
            <a href="<?= escapar(url('/historico/listar.php')) ?>" class="botao botao--secundario">VER HIST&Oacute;RICO</a>
        </div>
    </form>

    <div class="rolador__resultado" aria-live="polite">
        <!-- SVG grande: contém TODAS as 7 formas; só uma fica visível por vez,
             toggle controlado por dados.js conforme o radio selecionado. -->
        <svg id="dado-svg" class="dado-svg" viewBox="0 0 200 200" aria-hidden="true">
            <defs>
                <linearGradient id="gradDado" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%"   stop-color="#1a1a1a"/>
                    <stop offset="100%" stop-color="#0a0a0a"/>
                </linearGradient>
            </defs>

            <!-- d4: pirâmide -->
            <g class="dado-svg__forma" data-dado="d4" hidden display="none">
                <polygon points="100,25 180,170 20,170"
                         fill="url(#gradDado)" stroke="#ffd60a"
                         stroke-width="3" stroke-linejoin="round"/>
                <line x1="100" y1="25" x2="100" y2="170"
                      stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
            </g>

            <!-- d6: cubo (com bevel sutil) -->
            <g class="dado-svg__forma" data-dado="d6" hidden display="none">
                <rect x="35" y="35" width="130" height="130" rx="6"
                      fill="url(#gradDado)" stroke="#ffd60a" stroke-width="3"/>
                <line x1="35"  y1="35" x2="55"  y2="15" stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
                <line x1="165" y1="35" x2="145" y2="15" stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
                <line x1="55"  y1="15" x2="145" y2="15" stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
            </g>

            <!-- d8: octaedro (losango) -->
            <g class="dado-svg__forma" data-dado="d8" hidden display="none">
                <polygon points="100,20 180,100 100,180 20,100"
                         fill="url(#gradDado)" stroke="#ffd60a"
                         stroke-width="3" stroke-linejoin="round"/>
                <line x1="20"  y1="100" x2="180" y2="100" stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
                <line x1="100" y1="20"  x2="100" y2="180" stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
            </g>

            <!-- d10: pentágono duplo -->
            <g class="dado-svg__forma" data-dado="d10" hidden display="none">
                <polygon points="100,20 170,80 140,180 60,180 30,80"
                         fill="url(#gradDado)" stroke="#ffd60a"
                         stroke-width="3" stroke-linejoin="round"/>
                <line x1="30"  y1="80" x2="170" y2="80" stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
                <line x1="100" y1="20" x2="100" y2="80" stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
            </g>

            <!-- d12: dodecaedro / pentágono regular -->
            <g class="dado-svg__forma" data-dado="d12" hidden display="none">
                <polygon points="100,15 175,75 145,170 55,170 25,75"
                         fill="url(#gradDado)" stroke="#ffd60a"
                         stroke-width="3" stroke-linejoin="round"/>
                <polygon points="100,70 140,100 125,145 75,145 60,100"
                         fill="none" stroke="#ffd60a" stroke-width="1.4" opacity="0.4"/>
            </g>

            <!-- d20: icosaedro (default visível) -->
            <g class="dado-svg__forma" data-dado="d20">
                <polygon points="100,15 175,60 175,140 100,185 25,140 25,60"
                         fill="url(#gradDado)" stroke="#ffd60a"
                         stroke-width="3" stroke-linejoin="round"/>
                <line x1="100" y1="15" x2="100" y2="185" stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
                <line x1="25"  y1="60" x2="175" y2="140" stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
                <line x1="175" y1="60" x2="25"  y2="140" stroke="#ffd60a" stroke-width="1.4" opacity="0.45"/>
            </g>

            <!-- d100: esfera facetada -->
            <g class="dado-svg__forma" data-dado="d100" hidden display="none">
                <circle cx="100" cy="100" r="80"
                        fill="url(#gradDado)" stroke="#ffd60a" stroke-width="3"/>
                <ellipse cx="100" cy="100" rx="80" ry="35"
                         fill="none" stroke="#ffd60a" stroke-width="1.4" opacity="0.4"/>
                <ellipse cx="100" cy="100" rx="35" ry="80"
                         fill="none" stroke="#ffd60a" stroke-width="1.4" opacity="0.4"/>
                <line x1="20"  y1="100" x2="180" y2="100" stroke="#ffd60a" stroke-width="1.4" opacity="0.25"/>
                <line x1="100" y1="20"  x2="100" y2="180" stroke="#ffd60a" stroke-width="1.4" opacity="0.25"/>
            </g>

            <!-- Texto centralizado, atualizado via JS -->
            <text id="dado-svg-label" x="100" y="115" text-anchor="middle"
                  fill="#ffd60a"
                  font-family="JetBrains Mono, monospace"
                  font-size="38" font-weight="700">d20</text>
        </svg>

        <div id="rolador-valor" class="rolador__valor">--</div>
        <div id="rolador-rotulo" class="rolador__rotulo">>> AGUARDANDO INVOCACAO</div>
        <div id="rolador-dados" class="rolador__dados" aria-label="Dados rolados"></div>
    </div>
</div>

<script src="<?= escapar(url('/assets/js/dados.js')) ?>" defer></script>

<?php require __DIR__ . '/../views/rodape.php'; ?>
