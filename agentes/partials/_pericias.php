<?php
/**
 * Partial: Perícias canônicas com seletor de grau.
 *
 * Cada perícia tem:
 *   - select de grau (Destreinado / Treinado / Veterano / Especialista)
 *   - input numérico para bônus extra (ex: vantagens, magias)
 *
 * O atributo associado é renderizado como rótulo apenas (não editável).
 */
$catalogo = PericiaCatalog::pericias();
$periciasAgente = $dados['pericias'] ?? [];

$siglaAtrib = [
    'forca' => 'FOR', 'agilidade' => 'AGI', 'intelecto' => 'INT',
    'vigor' => 'VIG', 'presenca' => 'PRE',
];
?>
<details class="ficha-secao">
    <summary class="ficha-secao__titulo">
        <span class="ficha-secao__indice">06.</span> PER&Iacute;CIAS
    </summary>

    <div class="ficha-secao__corpo lista-pericias">
        <?php foreach ($catalogo as $nomeP => $atribBase):
            $atual = $periciasAgente[$nomeP] ?? ['grau' => 'Destreinado', 'bonus_extra' => 0];
        ?>
            <div class="pericia pericia--grau-<?= strtolower((string) $atual['grau']) ?>"
                 data-pericia-row>
                <span class="pericia__nome"><?= escapar((string) $nomeP) ?></span>
                <span class="pericia__atributo"><?= escapar($siglaAtrib[$atribBase] ?? '?') ?></span>
                <select name="pericias[<?= escapar($nomeP) ?>][grau]"
                        class="pericia__grau" data-pericia-grau>
                    <?php foreach (PericiaCatalog::GRAUS as $g): ?>
                        <option value="<?= escapar($g) ?>"
                            <?= (string) $atual['grau'] === $g ? 'selected' : '' ?>>
                            <?= escapar($g) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="pericias[<?= escapar($nomeP) ?>][bonus_extra]"
                       class="pericia__bonus" value="<?= (int) $atual['bonus_extra'] ?>"
                       min="-20" max="50" placeholder="+0">
            </div>
        <?php endforeach; ?>
    </div>
</details>
