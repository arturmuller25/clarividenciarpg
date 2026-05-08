<?php
/** Sub-partial: linha de ritual. */
$elemSlug = strtolower((string) ($r['elemento'] ?? 'conhecimento'));
?>
<div class="lista-dinamica__item ritual-linha ritual-linha--<?= escapar($elemSlug) ?>"
     data-item-row data-ritual-elemento="<?= escapar((string) ($r['elemento'] ?? 'Conhecimento')) ?>">
    <div class="ataque-linha__topo">
        <input type="text" name="rituais[<?= $i ?>][nome]"
               class="campo__entrada"
               value="<?= escapar((string) ($r['nome'] ?? '')) ?>"
               placeholder="Nome do ritual" maxlength="120">
        <button type="button" class="botao botao--pequeno botao--perigo"
                data-remover-item title="Remover ritual">&times;</button>
    </div>

    <div class="ritual-linha__grade">
        <div class="campo">
            <label class="campo__rotulo">CÍRCULO</label>
            <select name="rituais[<?= $i ?>][circulo]" class="campo__entrada">
                <?php for ($c = 1; $c <= 5; $c++): ?>
                    <option value="<?= $c ?>" <?= (int) ($r['circulo'] ?? 1) === $c ? 'selected' : '' ?>>
                        <?= $c ?>º
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="campo">
            <label class="campo__rotulo">ELEMENTO</label>
            <select name="rituais[<?= $i ?>][elemento]" class="campo__entrada"
                    data-ritual-elemento-select>
                <?php foreach (AgenteRepositorio::ELEMENTOS as $el): ?>
                    <option value="<?= escapar($el) ?>"
                        <?= ($r['elemento'] ?? 'Conhecimento') === $el ? 'selected' : '' ?>>
                        <?= escapar($el) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label class="campo__rotulo">CUSTO PE</label>
            <input type="number" name="rituais[<?= $i ?>][custo_pe]"
                   class="campo__entrada"
                   value="<?= (int) ($r['custo_pe'] ?? 1) ?>"
                   min="0" max="99">
        </div>
    </div>

    <textarea name="rituais[<?= $i ?>][descricao]"
              class="campo__entrada campo__entrada--textarea"
              rows="3" maxlength="2000"
              placeholder="Descrição, efeitos, condições..."><?= escapar((string) ($r['descricao'] ?? '')) ?></textarea>
</div>
