<?php
/** Sub-partial: linha de item de inventário. */
?>
<div class="lista-dinamica__item inventario-linha" data-item-row>
    <div class="ataque-linha__topo">
        <input type="text" name="inventario[<?= $i ?>][nome]"
               class="campo__entrada"
               value="<?= escapar((string) ($it['nome'] ?? '')) ?>"
               placeholder="Nome do item" maxlength="120">
        <button type="button" class="botao botao--pequeno botao--perigo"
                data-remover-item title="Remover item">&times;</button>
    </div>

    <div class="inventario-linha__grade">
        <div class="campo">
            <label class="campo__rotulo">CATEGORIA</label>
            <input type="text" name="inventario[<?= $i ?>][categoria]"
                   class="campo__entrada"
                   value="<?= escapar((string) ($it['categoria'] ?? '')) ?>"
                   placeholder="Arma, Munição, Consumível..." maxlength="40">
        </div>
        <div class="campo">
            <label class="campo__rotulo">ESPAÇOS</label>
            <input type="number" name="inventario[<?= $i ?>][espacos]"
                   class="campo__entrada"
                   value="<?= escapar((string) ($it['espacos'] ?? 1)) ?>"
                   step="0.5" min="0" max="99.99" data-inv-espacos>
        </div>
        <div class="campo">
            <label class="campo__rotulo">QTD</label>
            <input type="number" name="inventario[<?= $i ?>][quantidade]"
                   class="campo__entrada"
                   value="<?= (int) ($it['quantidade'] ?? 1) ?>"
                   min="1" max="9999" data-inv-quantidade>
        </div>
        <div class="campo campo--check">
            <label class="campo__rotulo">EQUIPADO</label>
            <input type="checkbox" name="inventario[<?= $i ?>][equipado]" value="1"
                   <?= !empty($it['equipado']) ? 'checked' : '' ?>>
        </div>
    </div>

    <textarea name="inventario[<?= $i ?>][descricao]"
              class="campo__entrada campo__entrada--textarea"
              rows="2" maxlength="1000"
              placeholder="Detalhes, propriedades..."><?= escapar((string) ($it['descricao'] ?? '')) ?></textarea>
</div>
