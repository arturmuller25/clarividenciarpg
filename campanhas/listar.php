<?php
declare(strict_types=1);

/**
 * Galeria de campanhas. Cada cartão exibe a capa, número de agentes
 * vinculados e atalhos para editar/abrir.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/CampanhaRepositorio.php';
require_once __DIR__ . '/../src/UploadHelper.php';

iniciarSessao();

try {
    $repo       = new CampanhaRepositorio();
    $campanhas  = $repo->listar();
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao consultar campanhas: ' . $e->getMessage());
    $campanhas = [];
}

$titulo      = 'CAMPANHAS';
$paginaAtiva = 'campanhas';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        CAMPANHAS DA ORDEM
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        TOTAL DE OPERAÇÕES ATIVAS: <strong><?= count($campanhas) ?></strong>
    </p>
    <div class="cabecalho-pagina__acoes">
        <a href="<?= escapar(url('/campanhas/formulario.php')) ?>" class="botao botao--primario">
            <span aria-hidden="true">+</span> NOVA CAMPANHA
        </a>
        <a href="<?= escapar(url('/index.php')) ?>" class="botao botao--secundario">VOLTAR AO PAINEL</a>
    </div>
</section>

<?php if (empty($campanhas)): ?>
    <div class="estado-vazio">
        <pre class="estado-vazio__arte" aria-hidden="true">
[ NENHUMA_CAMPANHA_REGISTRADA ]

   _______________
  |   ARQUIVO     |
  |    VAZIO      |
  |_______________|
        </pre>
        <p class="estado-vazio__texto">
            Nenhuma campanha foi registrada nos arquivos da Ordem.
            <a href="<?= escapar(url('/campanhas/formulario.php')) ?>" class="link">[INICIAR PRIMEIRA OPERAÇÃO]</a>.
        </p>
    </div>
<?php else: ?>
    <div class="galeria-campanhas">
        <?php foreach ($campanhas as $c):
            $capaUrl = UploadHelper::urlImagem('campanhas', $c['capa_arquivo'] ?? null);
        ?>
            <article class="cartao-campanha">
                <div class="cartao-campanha__capa"
                     <?php if ($capaUrl): ?>style="background-image: url('<?= escapar($capaUrl) ?>')"<?php endif; ?>>
                    <?php if (!$capaUrl): ?>
                        <span class="cartao-campanha__capa-vazia" aria-hidden="true">&#9678;</span>
                    <?php endif; ?>
                    <span class="cartao-campanha__sistema"><?= escapar((string) $c['sistema']) ?></span>
                </div>

                <div class="cartao-campanha__corpo">
                    <h2 class="cartao-campanha__nome"><?= escapar((string) $c['nome']) ?></h2>
                    <p class="cartao-campanha__descricao">
                        <?= escapar(mb_strimwidth((string) $c['descricao'], 0, 160, '...')) ?>
                    </p>
                    <dl class="cartao-campanha__stats">
                        <div class="cartao-campanha__stat">
                            <dt>AGENTES</dt>
                            <dd><?= (int) $c['total_agentes'] ?></dd>
                        </div>
                        <div class="cartao-campanha__stat">
                            <dt>NPCS</dt>
                            <dd><?= (int) $c['total_npcs'] ?></dd>
                        </div>
                        <div class="cartao-campanha__stat">
                            <dt>AMEAÇAS</dt>
                            <dd><?= (int) $c['total_criaturas'] ?></dd>
                        </div>
                    </dl>
                </div>

                <footer class="cartao-campanha__acoes">
                    <a href="<?= escapar(url('/campanhas/formulario.php?id=' . (int) $c['id'])) ?>"
                       class="botao botao--pequeno botao--primario">EDITAR</a>
                    <form action="<?= escapar(url('/campanhas/excluir.php')) ?>" method="POST"
                          data-confirmar="Excluir a campanha &quot;<?= escapar((string) $c['nome']) ?>&quot;? Os agentes/NPCs/criaturas vinculados ficarão sem campanha (não serão apagados)."
                          class="formulario-inline">
                        <input type="hidden" name="csrf_token" value="<?= escapar(gerarTokenCsrf()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button type="submit" class="botao botao--pequeno botao--perigo">ARQUIVAR</button>
                    </form>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../views/rodape.php'; ?>
