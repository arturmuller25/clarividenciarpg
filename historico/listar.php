<?php
declare(strict_types=1);

/**
 * Diário de Campanha - listagem cronológica das rolagens.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/LogRepositorio.php';

iniciarSessao();

try {
    $repo     = new LogRepositorio();
    $rolagens = $repo->listarRecentes(100);
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao consultar o Diario: ' . $e->getMessage());
    $rolagens = [];
}

$titulo      = 'DIÁRIO_DE_CAMPANHA';
$paginaAtiva = 'historico';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        DIÁRIO DE CAMPANHA
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        REGISTROS DE PERCEPÇÃO: <strong><?= count($rolagens) ?></strong> /
        ÚLTIMAS 100 ROLAGENS REGISTRADAS NO TERMINAL
    </p>
    <div class="cabecalho-pagina__acoes">
        <a href="<?= escapar(url('/rolagem/index.php')) ?>" class="botao botao--primario">NOVA ROLAGEM</a>
        <a href="<?= escapar(url('/index.php')) ?>" class="botao botao--secundario">VOLTAR AO PAINEL</a>
    </div>
</section>

<?php if (empty($rolagens)): ?>
    <div class="estado-vazio">
        <pre class="estado-vazio__arte" aria-hidden="true">
[ DIARIO_VAZIO ]

  ___________
 |           |
 |  SILENT   |
 |           |
 |___________|
        </pre>
        <p class="estado-vazio__texto">
            Nenhuma rolagem foi registrada ainda. Acesse o
            <a href="<?= escapar(url('/rolagem/index.php')) ?>" class="link">[RITUAL DE CLARIVIDENCIA]</a>
            para iniciar.
        </p>
    </div>
<?php else: ?>
    <div class="tabela-envoltorio">
        <table class="tabela">
            <caption class="visualmente-oculto">Histórico cronológico de rolagens</caption>
            <thead>
                <tr>
                    <th scope="col">QUANDO</th>
                    <th scope="col">QUEM ROLOU</th>
                    <th scope="col">MOTIVO</th>
                    <th scope="col">TIPO</th>
                    <th scope="col">DADOS</th>
                    <th scope="col">RESULTADO</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rolagens as $r):
                    $brutos = json_decode((string) $r['resultados_brutos'], true);
                    if (!is_array($brutos)) { $brutos = []; }
                    $tipoDado   = (string) ($r['tipo_dado'] ?? 'd20');
                    $ehCritico  = (bool) $r['eh_critico'];
                    $ehDesastre = (bool) $r['eh_desastre'];
                    $classeRes  = $ehCritico ? 'is-critico' : ($ehDesastre ? 'is-desastre' : '');
                    $qtd        = (int) $r['quantidade_dados'];
                    // Para d20 com regra de Ordem Paranormal, mostrar Nd20 ou 2d20 (desastre).
                    // Para outros tipos, sempre 1 dado simples.
                    $rotuloDados = $tipoDado === 'd20'
                        ? (($qtd === 0 ? '2' : (string) $qtd) . 'd20')
                        : ('1' . $tipoDado);
                ?>
                    <tr>
                        <td class="tabela__data" data-label="Quando">
                            <?= escapar(substr((string) $r['rolado_em'], 0, 16)) ?>
                        </td>
                        <td data-label="Quem rolou"><strong><?= escapar((string) $r['quem_rolou']) ?></strong></td>
                        <td class="tabela__truncar" data-label="Motivo" title="<?= escapar((string) $r['descricao']) ?>">
                            <?= escapar((string) $r['descricao']) ?>
                        </td>
                        <td data-label="Tipo">
                            <span class="tabela__tipo-dado"><?= escapar($tipoDado) ?></span>
                        </td>
                        <td data-label="Dados">
                            <span title="<?= escapar(implode(', ', array_map('strval', $brutos))) ?>">
                                <?= escapar($rotuloDados) ?>
                                [<?= escapar(implode(',', array_map('strval', $brutos))) ?>]
                            </span>
                        </td>
                        <td data-label="Resultado">
                            <span class="tabela__resultado <?= $classeRes ?>">
                                <?= (int) $r['resultado_final'] ?>
                            </span>
                            <?php if ($ehCritico): ?>
                                <span class="tabela__tag is-critico">CR&Iacute;TICO</span>
                            <?php endif; ?>
                            <?php if ($ehDesastre): ?>
                                <span class="tabela__tag is-desastre">FALHA</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../views/rodape.php'; ?>
