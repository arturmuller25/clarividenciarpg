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
            <label for="quantidade" class="campo__rotulo">
                <span class="campo__indice">03.</span> ATRIBUTO (NUMERO DE DADOS)
            </label>
            <input type="number" id="quantidade" name="quantidade"
                   class="campo__entrada" min="0" max="10" value="3" required>
            <small class="campo__ajuda">
                0 = DESASTRE (rola 2, pega menor). 1-10 = vantagem (rola N, pega maior).
            </small>
        </div>

        <div class="formulario__acoes">
            <button type="submit" class="botao botao--primario">INVOCAR ROLAGEM</button>
            <a href="/historico/listar.php" class="botao botao--secundario">VER HISTORICO</a>
        </div>
    </form>

    <div class="rolador__resultado" aria-live="polite">
        <svg id="dado-svg" class="dado-svg" viewBox="0 0 200 200" aria-hidden="true">
            <defs>
                <linearGradient id="gradDado" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%"   stop-color="#1a1a1a"/>
                    <stop offset="100%" stop-color="#0a0a0a"/>
                </linearGradient>
            </defs>
            <polygon points="100,10 190,55 190,145 100,190 10,145 10,55"
                     fill="url(#gradDado)" stroke="#ffd60a"
                     stroke-width="2" stroke-linejoin="round"/>
            <polygon points="100,10 190,55 100,100 10,55"
                     fill="none" stroke="#ffd60a"
                     stroke-width="1.2" opacity="0.45"/>
            <line x1="100" y1="100" x2="100" y2="190" stroke="#ffd60a"
                  stroke-width="1.2" opacity="0.45"/>
            <text x="100" y="115" text-anchor="middle" fill="#ffd60a"
                  font-family="JetBrains Mono, monospace"
                  font-size="42" font-weight="700">d20</text>
        </svg>

        <div id="rolador-valor" class="rolador__valor">--</div>
        <div id="rolador-rotulo" class="rolador__rotulo">>> AGUARDANDO INVOCACAO</div>
        <div id="rolador-dados" class="rolador__dados" aria-label="Dados rolados"></div>
    </div>
</div>

<script src="/assets/js/dados.js" defer></script>

<?php require __DIR__ . '/../views/rodape.php'; ?>
