<?php
/**
 * Sub-partial: uma linha de ataque (usada em loop pelo _ataques.php
 * e como template clonado pelo JS).
 *
 * Espera variáveis no escopo:
 *   $i  índice (int ou "__i__" no template)
 *   $a  array de dados do ataque
 */
?>
<div class="lista-dinamica__item ataque-linha" data-item-row>
    <div class="ataque-linha__topo">
        <input type="text" name="ataques[<?= $i ?>][nome]"
               class="campo__entrada ataque-linha__nome"
               value="<?= escapar((string) ($a['nome'] ?? '')) ?>"
               placeholder="Nome do ataque (ex: Faca de prata, Pistola .45)" maxlength="80">
        <button type="button" class="botao botao--pequeno botao--perigo"
                data-remover-item title="Remover ataque">&times;</button>
    </div>

    <div class="ataque-linha__grade">
        <div class="campo">
            <label class="campo__rotulo">ATRIBUTO</label>
            <select name="ataques[<?= $i ?>][atributo_base]"
                    class="campo__entrada ataque-linha__atributo"
                    data-ataque-atributo>
                <?php foreach (AgenteRepositorio::ATRIBUTOS as $atr):
                    $selecionado = ($a['atributo_base'] ?? 'forca') === $atr;
                    $sigla = strtoupper(substr($atr, 0, 3));
                ?>
                    <option value="<?= escapar($atr) ?>" <?= $selecionado ? 'selected' : '' ?>>
                        <?= escapar($sigla) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label class="campo__rotulo">BÔNUS ARMA</label>
            <input type="number" name="ataques[<?= $i ?>][bonus_arma]"
                   class="campo__entrada ataque-linha__bonus"
                   value="<?= (int) ($a['bonus_arma'] ?? 0) ?>"
                   min="-20" max="50" data-ataque-bonus-arma>
        </div>
        <div class="campo">
            <label class="campo__rotulo">BÔNUS EXTRA</label>
            <input type="number" name="ataques[<?= $i ?>][bonus_extra]"
                   class="campo__entrada ataque-linha__bonus"
                   value="<?= (int) ($a['bonus_extra'] ?? 0) ?>"
                   min="-20" max="50" data-ataque-bonus-extra>
        </div>
        <div class="campo">
            <label class="campo__rotulo">TOTAL</label>
            <output class="ataque-linha__total" data-ataque-total>+0</output>
        </div>
        <div class="campo">
            <label class="campo__rotulo">DANO</label>
            <input type="text" name="ataques[<?= $i ?>][dano]"
                   class="campo__entrada"
                   value="<?= escapar((string) ($a['dano'] ?? '1d4')) ?>"
                   placeholder="2d6+4" maxlength="40">
        </div>
        <div class="campo">
            <label class="campo__rotulo">TIPO DANO</label>
            <input type="text" name="ataques[<?= $i ?>][tipo_dano]"
                   class="campo__entrada"
                   value="<?= escapar((string) ($a['tipo_dano'] ?? '')) ?>"
                   placeholder="Físico / Sangue" maxlength="40">
        </div>
    </div>

    <textarea name="ataques[<?= $i ?>][descricao]"
              class="campo__entrada campo__entrada--textarea"
              rows="2" maxlength="1000"
              placeholder="Notas, propriedades especiais..."><?= escapar((string) ($a['descricao'] ?? '')) ?></textarea>
</div>
