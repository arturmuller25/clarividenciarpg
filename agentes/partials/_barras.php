<?php
/**
 * Partial: Barras de Vida (PV), Sanidade (SAN) e Esforço (PE).
 * As barras visuais são atualizadas em tempo real pelo agente.js
 * conforme o usuário muda os valores numéricos.
 */
$barras = [
    ['rotulo' => 'PV',  'cor' => 'pv',  'desc' => 'PONTOS DE VIDA',     'campo' => 'pv'],
    ['rotulo' => 'SAN', 'cor' => 'san', 'desc' => 'SANIDADE',           'campo' => 'san'],
    ['rotulo' => 'PE',  'cor' => 'pe',  'desc' => 'PONTOS DE ESFORÇO',  'campo' => 'pe'],
];
?>
<details class="ficha-secao" open>
    <summary class="ficha-secao__titulo">
        <span class="ficha-secao__indice">02.</span> BARRAS VITAIS
    </summary>

    <div class="ficha-secao__corpo">
        <?php foreach ($barras as $b):
            $atual  = (int) $dados['agente'][$b['campo'] . '_atual'];
            $maximo = (int) $dados['agente'][$b['campo'] . '_maximo'];
            $pct    = $maximo > 0 ? round(($atual / $maximo) * 100) : 0;
        ?>
            <div class="barra-ficha barra-ficha--<?= $b['cor'] ?>" data-barra="<?= $b['cor'] ?>">
                <div class="barra-ficha__cabecalho">
                    <span class="barra-ficha__rotulo"><?= escapar($b['rotulo']) ?></span>
                    <span class="barra-ficha__desc"><?= escapar($b['desc']) ?></span>
                </div>
                <div class="barra-ficha__trilho">
                    <div class="barra-ficha__preenchimento" style="width: <?= $pct ?>%"></div>
                </div>
                <div class="barra-ficha__inputs">
                    <label class="campo__rotulo">ATUAL</label>
                    <input type="number" name="<?= $b['campo'] ?>_atual"
                           class="campo__entrada barra-ficha__input"
                           value="<?= $atual ?>" min="0" max="999"
                           data-barra-atual>
                    <span class="barra-ficha__separador">/</span>
                    <label class="campo__rotulo">MÁXIMO</label>
                    <input type="number" name="<?= $b['campo'] ?>_maximo"
                           class="campo__entrada barra-ficha__input"
                           value="<?= $maximo ?>" min="0" max="999"
                           data-barra-maximo>
                </div>
                <?php if (isset($erros[$b['campo'] . '_atual'])): ?>
                    <small class="campo__erro"><?= escapar($erros[$b['campo'] . '_atual']) ?></small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</details>
