<?php
/**
 * Partial: Identidade do Agente.
 * Espera variáveis: $dados (array do agente), $erros, $campanhasOpts, $fotoUrl.
 */
?>
<details class="ficha-secao" open>
    <summary class="ficha-secao__titulo">
        <span class="ficha-secao__indice">01.</span> IDENTIDADE
    </summary>

    <div class="ficha-secao__corpo ficha-identidade">
        <div class="ficha-identidade__foto">
            <small class="campo__ajuda" style="margin-bottom:6px">Recortada em 1:1. Máx. 4 MB.</small>
            <div data-cropper data-cropper-input="foto"
                 <?php if (!empty($fotoUrl)): ?>data-cropper-existing="<?= escapar($fotoUrl) ?>"<?php endif; ?>>
                <input type="file" name="foto" id="foto"
                       class="campo__entrada campo__entrada--arquivo"
                       accept="image/jpeg,image/png,image/webp">
                <?php if (!empty($fotoUrl)): ?>
                    <label class="upload-preview__remover" style="margin-top: 6px">
                        <input type="checkbox" name="remover_foto" value="1">
                        <span>Remover foto atual</span>
                    </label>
                <?php endif; ?>
            </div>
        </div>

        <div class="ficha-identidade__campos">
            <div class="campo <?= isset($erros['nome']) ? 'campo--invalido' : '' ?>">
                <label for="nome" class="campo__rotulo">
                    NOME <span class="campo__obrigatorio" aria-hidden="true">*</span>
                </label>
                <input type="text" id="nome" name="nome" class="campo__entrada"
                       value="<?= escapar((string) $dados['agente']['nome']) ?>"
                       maxlength="120" required autocomplete="off">
                <small class="campo__erro" data-erro-para="nome">
                    <?= isset($erros['nome']) ? escapar($erros['nome']) : '' ?>
                </small>
            </div>

            <div class="formulario__linha">
                <div class="campo <?= isset($erros['jogador']) ? 'campo--invalido' : '' ?>">
                    <label for="jogador" class="campo__rotulo">JOGADOR</label>
                    <input type="text" id="jogador" name="jogador" class="campo__entrada"
                           value="<?= escapar((string) ($dados['agente']['jogador'] ?? '')) ?>"
                           maxlength="120" autocomplete="off">
                </div>
                <div class="campo <?= isset($erros['origem']) ? 'campo--invalido' : '' ?>">
                    <label for="origem" class="campo__rotulo">ORIGEM</label>
                    <input type="text" id="origem" name="origem" class="campo__entrada"
                           value="<?= escapar((string) ($dados['agente']['origem'] ?? '')) ?>"
                           maxlength="60" placeholder="Ex: Acadêmico, Cultista Arrependido"
                           autocomplete="off">
                </div>
            </div>

            <div class="formulario__linha">
                <div class="campo">
                    <label for="classe" class="campo__rotulo">CLASSE</label>
                    <select id="classe" name="classe" class="campo__entrada">
                        <?php foreach (AgenteRepositorio::CLASSES as $c): ?>
                            <option value="<?= escapar($c) ?>"
                                <?= $dados['agente']['classe'] === $c ? 'selected' : '' ?>>
                                <?= escapar($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo <?= isset($erros['nex']) ? 'campo--invalido' : '' ?>">
                    <label for="nex" class="campo__rotulo">NEX (%)</label>
                    <input type="number" id="nex" name="nex" class="campo__entrada"
                           value="<?= (int) $dados['agente']['nex'] ?>"
                           min="1" max="99" required>
                    <small class="campo__erro" data-erro-para="nex">
                        <?= isset($erros['nex']) ? escapar($erros['nex']) : '' ?>
                    </small>
                </div>
                <div class="campo">
                    <label for="campanha_id" class="campo__rotulo">CAMPANHA</label>
                    <select id="campanha_id" name="campanha_id" class="campo__entrada">
                        <option value="">[ SEM CAMPANHA ]</option>
                        <?php foreach ($campanhasOpts as $cmp): ?>
                            <option value="<?= (int) $cmp['id'] ?>"
                                <?= (int) ($dados['agente']['campanha_id'] ?? 0) === (int) $cmp['id'] ? 'selected' : '' ?>>
                                <?= escapar((string) $cmp['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</details>
