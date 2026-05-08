<?php
/**
 * Partial: Combate / Ataques.
 *
 * Lista dinâmica: "+ Adicionar ataque" clona o template em JS.
 * Cada linha calcula em tempo real: TOTAL = atributo + bônus_arma + bônus_extra.
 * O JS lê os valores dos inputs com data-atributo da seção 03.
 */
$ataques = $dados['ataques'] ?? [];
?>
<details class="ficha-secao">
    <summary class="ficha-secao__titulo">
        <span class="ficha-secao__indice">07.</span> COMBATE
    </summary>

    <div class="ficha-secao__corpo">
        <p class="ficha-secao__ajuda">
            Cada ataque calcula automaticamente o bônus total
            <code>[Atributo] + [Arma] + [Bônus]</code> conforme você edita.
        </p>

        <div class="lista-dinamica" id="lista-ataques" data-lista="ataques">
            <?php foreach ($ataques as $i => $a): ?>
                <?php include __DIR__ . '/_ataque_linha.php'; ?>
            <?php endforeach; ?>
        </div>

        <button type="button" class="botao botao--pequeno"
                data-adicionar="ataque">+ ADICIONAR ATAQUE</button>

        <!-- Template para clonagem via JS. __i__ é substituído pelo índice. -->
        <template id="tpl-ataque">
            <?php
            $i = '__i__';
            $a = ['nome' => '', 'atributo_base' => 'forca', 'bonus_arma' => 0,
                  'bonus_extra' => 0, 'dano' => '1d4', 'tipo_dano' => '', 'descricao' => ''];
            include __DIR__ . '/_ataque_linha.php';
            ?>
        </template>
    </div>
</details>
