<?php
declare(strict_types=1);

/**
 * Cabeçalho compartilhado de todas as telas do Terminal da Ordem.
 *
 * Variáveis esperadas (opcionais) no escopo de quem inclui:
 *   string $titulo       Título exibido na aba do navegador.
 *   string $paginaAtiva  Slug do menu corrente: 'inicio' | 'npcs' | ''.
 */

iniciarSessao();

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$titulo      = $titulo      ?? 'TERMINAL DA ORDEM';
$paginaAtiva = $paginaAtiva ?? '';
$mensagens   = consumirFlash();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#000000">
    <title><?= escapar($titulo) ?> // TERMINAL DA ORDEM</title>
    <link rel="stylesheet" href="<?= escapar(url('/assets/css/terminal.css')) ?>">
</head>
<body>
    <div class="terminal">
        <header class="terminal__topo" role="banner">
            <a href="<?= escapar(url('/index.php')) ?>" class="terminal__marca" aria-label="Clarividência Paranormal - início">
                <span class="terminal__prompt">&gt;</span>
                <span class="terminal__marca-nome">Clarivid&ecirc;ncia
                    <span class="terminal__marca-realce">Paranormal</span>
                </span>
                <span class="terminal__cursor" aria-hidden="true">_</span>
            </a>

            <!-- Menu hambúrguer pure-CSS: checkbox invisível + label clicável + nav -->
            <input type="checkbox" id="menu-toggle" class="menu-hamburger__toggle" aria-hidden="true">
            <label for="menu-toggle" class="menu-hamburger__botao" aria-label="Abrir menu de navegação">
                <span class="menu-hamburger__linha"></span>
                <span class="menu-hamburger__linha"></span>
                <span class="menu-hamburger__linha"></span>
                <span class="menu-hamburger__rotulo">MENU</span>
            </label>
            <label for="menu-toggle" class="menu-hamburger__overlay" aria-hidden="true"></label>

            <nav class="menu-hamburger__painel" aria-label="Navegação principal">
                <span class="menu-hamburger__cabecalho">
                    <span class="menu-hamburger__sigilo" aria-hidden="true">&#9678;</span>
                    ARQUIVOS_DA_ORDEM
                </span>
                <a href="<?= escapar(url('/index.php')) ?>"
                   class="menu-hamburger__link <?= $paginaAtiva === 'inicio' ? 'is-ativo' : '' ?>">
                    <span class="menu-hamburger__num">// 00</span> PAINEL
                </a>
                <a href="<?= escapar(url('/campanhas/listar.php')) ?>"
                   class="menu-hamburger__link <?= $paginaAtiva === 'campanhas' ? 'is-ativo' : '' ?>">
                    <span class="menu-hamburger__num">// 01</span> CAMPANHAS
                </a>
                <a href="<?= escapar(url('/agentes/listar.php')) ?>"
                   class="menu-hamburger__link <?= $paginaAtiva === 'agentes' ? 'is-ativo' : '' ?>">
                    <span class="menu-hamburger__num">// 02</span> AGENTES
                </a>
                <a href="<?= escapar(url('/npcs/listar.php')) ?>"
                   class="menu-hamburger__link <?= $paginaAtiva === 'npcs' ? 'is-ativo' : '' ?>">
                    <span class="menu-hamburger__num">// 03</span> NPCS
                </a>
                <a href="<?= escapar(url('/criaturas/listar.php')) ?>"
                   class="menu-hamburger__link <?= $paginaAtiva === 'bestiario' ? 'is-ativo' : '' ?>">
                    <span class="menu-hamburger__num">// 04</span> BESTI&Aacute;RIO
                </a>
                <a href="<?= escapar(url('/rolagem/index.php')) ?>"
                   class="menu-hamburger__link <?= $paginaAtiva === 'rolagem' ? 'is-ativo' : '' ?>">
                    <span class="menu-hamburger__num">// 05</span> ROLAGENS
                </a>
                <a href="<?= escapar(url('/historico/listar.php')) ?>"
                   class="menu-hamburger__link <?= $paginaAtiva === 'historico' ? 'is-ativo' : '' ?>">
                    <span class="menu-hamburger__num">// 06</span> HIST&Oacute;RICO
                </a>
                <span class="menu-hamburger__rodape">// CLASSIFICADO</span>
            </nav>
        </header>

        <?php if (!empty($mensagens)): ?>
            <div class="terminal__flashes" role="status" aria-live="polite">
                <?php foreach ($mensagens as $msg): ?>
                    <div class="flash flash--<?= escapar($msg['tipo']) ?>">
                        <span class="flash__rotulo">[<?= strtoupper(escapar($msg['tipo'])) ?>]</span>
                        <span class="flash__texto"><?= escapar($msg['mensagem']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main class="terminal__corpo">
