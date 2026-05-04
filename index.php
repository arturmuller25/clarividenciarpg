<?php
declare(strict_types=1);

/**
 * Painel do Mestre - tela inicial.
 * Exibe os módulos disponíveis e o status de cada um.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/sessao.php';

$titulo      = 'PAINEL_DO_MESTRE';
$paginaAtiva = 'inicio';
require __DIR__ . '/views/cabecalho.php';
?>

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

<div class="grade-modulos">
    <a href="/npcs/listar.php" class="cartao-modulo cartao-modulo--ativo">
        <span class="cartao-modulo__codigo">// 01</span>
        <h2 class="cartao-modulo__nome">NPCS</h2>
        <p class="cartao-modulo__descricao">
            Cadastro e dossie de personagens nao-jogaveis.
        </p>
        <span class="cartao-modulo__status">[OPERACIONAL]</span>
    </a>

    <a href="/criaturas/listar.php" class="cartao-modulo cartao-modulo--ativo">
        <span class="cartao-modulo__codigo">// 02</span>
        <h2 class="cartao-modulo__nome">BESTIARIO</h2>
        <p class="cartao-modulo__descricao">
            Catalogo de criaturas paranormais classificadas por elemento.
        </p>
        <span class="cartao-modulo__status">[OPERACIONAL]</span>
    </a>

    <a href="/rolagem/index.php" class="cartao-modulo cartao-modulo--ativo">
        <span class="cartao-modulo__codigo">// 03</span>
        <h2 class="cartao-modulo__nome">ROLAGEM_RITUAL</h2>
        <p class="cartao-modulo__descricao">
            Lancador de dados d20 com regra do maior valor.
        </p>
        <span class="cartao-modulo__status">[OPERACIONAL]</span>
    </a>

    <a href="/historico/listar.php" class="cartao-modulo cartao-modulo--ativo">
        <span class="cartao-modulo__codigo">// 04</span>
        <h2 class="cartao-modulo__nome">HISTORICO</h2>
        <p class="cartao-modulo__descricao">
            Registro de todas as rolagens efetuadas no terminal.
        </p>
        <span class="cartao-modulo__status">[OPERACIONAL]</span>
    </a>
</div>

<?php require __DIR__ . '/views/rodape.php'; ?>
