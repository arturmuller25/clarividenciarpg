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
    <link rel="stylesheet" href="/assets/css/terminal.css">
</head>
<body>
    <div class="terminal">
        <header class="terminal__topo" role="banner">
            <a href="/index.php" class="terminal__marca" aria-label="Terminal da Ordem - início">
                <span class="terminal__prompt">&gt;</span>
                <span>TERMINAL_DA_ORDEM</span>
                <span class="terminal__cursor" aria-hidden="true">_</span>
            </a>
            <nav class="terminal__nav" aria-label="Navegação principal">
                <a href="/npcs/listar.php"
                   class="terminal__link <?= $paginaAtiva === 'npcs' ? 'is-ativo' : '' ?>">[NPCS]</a>
                <a href="/criaturas/listar.php"
                   class="terminal__link <?= $paginaAtiva === 'bestiario' ? 'is-ativo' : '' ?>">[BESTIARIO]</a>
                <a href="/rolagem/index.php"
                   class="terminal__link <?= $paginaAtiva === 'rolagem' ? 'is-ativo' : '' ?>">[ROLAGEM]</a>
                <a href="/historico/listar.php"
                   class="terminal__link <?= $paginaAtiva === 'historico' ? 'is-ativo' : '' ?>">[HISTORICO]</a>
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
