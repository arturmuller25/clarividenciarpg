<?php
/**
 * Partial: Atributos canônicos de Ordem Paranormal.
 * FOR / AGI / INT / VIG / PRE — escala 0..6.
 *
 * Inputs com data-atributo identificam-se para que o agente.js use seus
 * valores no cálculo automático de ataques.
 */
$atributos = [
    ['campo' => 'forca',     'sigla' => 'FOR', 'nome' => 'FORÇA',     'desc' => 'Força física, esforço bruto'],
    ['campo' => 'agilidade', 'sigla' => 'AGI', 'nome' => 'AGILIDADE', 'desc' => 'Reflexos, destreza'],
    ['campo' => 'intelecto', 'sigla' => 'INT', 'nome' => 'INTELECTO', 'desc' => 'Raciocínio, conhecimento'],
    ['campo' => 'vigor',     'sigla' => 'VIG', 'nome' => 'VIGOR',     'desc' => 'Resistência, fôlego'],
    ['campo' => 'presenca',  'sigla' => 'PRE', 'nome' => 'PRESENÇA',  'desc' => 'Carisma, impacto social'],
];
?>
<details class="ficha-secao" open>
    <summary class="ficha-secao__titulo">
        <span class="ficha-secao__indice">03.</span> ATRIBUTOS
    </summary>

    <div class="ficha-secao__corpo grade-atributos">
        <?php foreach ($atributos as $a): ?>
            <div class="atributo">
                <span class="atributo__sigla"><?= escapar($a['sigla']) ?></span>
                <input type="number" name="<?= $a['campo'] ?>"
                       id="atributo-<?= $a['campo'] ?>"
                       class="atributo__valor"
                       value="<?= (int) $dados['agente'][$a['campo']] ?>"
                       min="0" max="6"
                       data-atributo="<?= $a['campo'] ?>">
                <span class="atributo__nome"><?= escapar($a['nome']) ?></span>
                <span class="atributo__desc"><?= escapar($a['desc']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</details>
