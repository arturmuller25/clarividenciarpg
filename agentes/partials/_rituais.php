<?php
/**
 * Partial: Rituais conhecidos.
 *
 * Lista dinâmica. Cada ritual: nome, círculo (1-5), elemento, custo PE,
 * descrição. A cor da borda varia conforme o elemento.
 */
$rituais = $dados['rituais'] ?? [];
?>
<details class="ficha-secao">
    <summary class="ficha-secao__titulo">
        <span class="ficha-secao__indice">09.</span> RITUAIS
    </summary>

    <div class="ficha-secao__corpo">
        <p class="ficha-secao__ajuda">
            Rituais aprendidos pelo agente. Custo em PE pode variar com vontades extras durante a casting.
        </p>

        <div class="lista-dinamica" id="lista-rituais" data-lista="rituais">
            <?php foreach ($rituais as $i => $r): ?>
                <?php include __DIR__ . '/_ritual_linha.php'; ?>
            <?php endforeach; ?>
        </div>

        <button type="button" class="botao botao--pequeno"
                data-adicionar="ritual">+ ADICIONAR RITUAL</button>

        <template id="tpl-ritual">
            <?php
            $i = '__i__';
            $r = ['nome' => '', 'circulo' => 1, 'elemento' => 'Conhecimento',
                  'custo_pe' => 1, 'descricao' => ''];
            include __DIR__ . '/_ritual_linha.php';
            ?>
        </template>
    </div>
</details>
