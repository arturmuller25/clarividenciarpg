<?php
/**
 * Partial: Narrativa (Aparência, Personalidade, Histórico, Objetivos).
 */
$campos = [
    ['campo' => 'aparencia',     'rotulo' => 'APARÊNCIA',     'placeholder' => 'Físico, vestimenta, marcas, posturas...'],
    ['campo' => 'personalidade', 'rotulo' => 'PERSONALIDADE', 'placeholder' => 'Traços, manias, virtudes, defeitos...'],
    ['campo' => 'historico',     'rotulo' => 'HISTÓRICO',     'placeholder' => 'Passado, eventos formadores, primeiro contato com o Outro Lado...'],
    ['campo' => 'objetivos',     'rotulo' => 'OBJETIVOS',     'placeholder' => 'Metas pessoais, vinganças, propósitos...'],
];
?>
<details class="ficha-secao">
    <summary class="ficha-secao__titulo">
        <span class="ficha-secao__indice">05.</span> NARRATIVA
    </summary>

    <div class="ficha-secao__corpo">
        <?php foreach ($campos as $c): ?>
            <div class="campo">
                <label for="<?= $c['campo'] ?>" class="campo__rotulo">
                    <?= escapar($c['rotulo']) ?>
                </label>
                <textarea id="<?= $c['campo'] ?>" name="<?= $c['campo'] ?>"
                          class="campo__entrada campo__entrada--textarea"
                          rows="4" maxlength="5000"
                          placeholder="<?= escapar($c['placeholder']) ?>"><?= escapar((string) ($dados['agente'][$c['campo']] ?? '')) ?></textarea>
            </div>
        <?php endforeach; ?>
    </div>
</details>
