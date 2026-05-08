<?php
/**
 * Partial: Inventário com tracking de espaços ocupados.
 *
 * Lista dinâmica. Cada item: nome, descrição, categoria, espaços (decimal),
 * quantidade, equipado.
 *
 * O agente.js calcula em tempo real "Total: X espaços" somando
 * (espaços × quantidade) de todas as linhas.
 */
$itens = $dados['inventario'] ?? [];
?>
<details class="ficha-secao">
    <summary class="ficha-secao__titulo">
        <span class="ficha-secao__indice">08.</span> INVENT&Aacute;RIO
    </summary>

    <div class="ficha-secao__corpo">
        <div class="lista-dinamica__resumo">
            <span>Total ocupado:</span>
            <strong data-inventario-total>0.00</strong>
            <span>espaços</span>
        </div>

        <div class="lista-dinamica" id="lista-inventario" data-lista="inventario">
            <?php foreach ($itens as $i => $it): ?>
                <?php include __DIR__ . '/_inventario_linha.php'; ?>
            <?php endforeach; ?>
        </div>

        <button type="button" class="botao botao--pequeno"
                data-adicionar="inventario">+ ADICIONAR ITEM</button>

        <template id="tpl-inventario">
            <?php
            $i = '__i__';
            $it = ['nome' => '', 'descricao' => '', 'categoria' => '',
                   'espacos' => 1, 'quantidade' => 1, 'equipado' => false];
            include __DIR__ . '/_inventario_linha.php';
            ?>
        </template>
    </div>
</details>
