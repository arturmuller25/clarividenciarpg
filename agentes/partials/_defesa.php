<?php
/**
 * Partial: Defesa, Resistências e Proficiências.
 */
?>
<details class="ficha-secao">
    <summary class="ficha-secao__titulo">
        <span class="ficha-secao__indice">04.</span> DEFESA &amp; RESIST&Ecirc;NCIAS
    </summary>

    <div class="ficha-secao__corpo">
        <div class="formulario__linha">
            <div class="campo">
                <label for="defesa" class="campo__rotulo">DEFESA</label>
                <input type="number" id="defesa" name="defesa" class="campo__entrada"
                       value="<?= (int) $dados['agente']['defesa'] ?>" min="0" max="99">
                <small class="campo__ajuda">Valor base de defesa contra ataques.</small>
            </div>
            <div class="campo">
                <label for="pe_por_turno" class="campo__rotulo">PE / TURNO</label>
                <input type="number" id="pe_por_turno" name="pe_por_turno" class="campo__entrada"
                       value="<?= (int) $dados['agente']['pe_por_turno'] ?>" min="0" max="20">
                <small class="campo__ajuda">PE recuperados por turno.</small>
            </div>
            <div class="campo">
                <label for="deslocamento" class="campo__rotulo">DESLOCAMENTO (m)</label>
                <input type="number" id="deslocamento" name="deslocamento" class="campo__entrada"
                       value="<?= (int) $dados['agente']['deslocamento'] ?>" min="0" max="50">
            </div>
        </div>

        <div class="campo">
            <label for="resistencias" class="campo__rotulo">RESISTÊNCIAS</label>
            <textarea id="resistencias" name="resistencias"
                      class="campo__entrada campo__entrada--textarea"
                      rows="3" maxlength="5000"
                      placeholder="Imune a venenos. Resistência 5 a frio. Vulnerável a fogo..."><?= escapar((string) ($dados['agente']['resistencias'] ?? '')) ?></textarea>
        </div>

        <div class="campo">
            <label for="proficiencias" class="campo__rotulo">PROFICIÊNCIAS</label>
            <textarea id="proficiencias" name="proficiencias"
                      class="campo__entrada campo__entrada--textarea"
                      rows="3" maxlength="5000"
                      placeholder="Armas leves. Pistolas. Armaduras leves..."><?= escapar((string) ($dados['agente']['proficiencias'] ?? '')) ?></textarea>
        </div>
    </div>
</details>
