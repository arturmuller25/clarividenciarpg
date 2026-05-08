<?php
declare(strict_types=1);

/**
 * Bestiário - galeria de criaturas paranormais com filtro por elemento.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/CriaturaRepositorio.php';
require_once __DIR__ . '/../src/UploadHelper.php';

iniciarSessao();

$filtroElemento = isset($_GET['elemento']) ? (string) $_GET['elemento'] : '';

try {
    $repo      = new CriaturaRepositorio();
    $criaturas = $repo->listar($filtroElemento !== '' ? $filtroElemento : null);
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao consultar o Bestiario: ' . $e->getMessage());
    $criaturas = [];
}

$titulo      = 'BESTIÁRIO';
$paginaAtiva = 'bestiario';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        BESTIÁRIO PARANORMAL
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        AMEAÇAS CATALOGADAS: <strong><?= count($criaturas) ?></strong>
    </p>
    <div class="cabecalho-pagina__acoes">
        <a href="<?= escapar(url('/criaturas/formulario.php')) ?>" class="botao botao--primario">
            <span aria-hidden="true">+</span> NOVA CRIATURA
        </a>
        <a href="<?= escapar(url('/index.php')) ?>" class="botao botao--secundario">VOLTAR AO PAINEL</a>
    </div>
</section>

<form method="GET" class="filtros" aria-label="Filtros de criaturas">
    <div class="filtros__grupo">
        <label for="filtro-elemento" class="filtros__rotulo">// ELEMENTO</label>
        <select id="filtro-elemento" name="elemento" class="filtros__select">
            <option value="">[ TODOS ]</option>
            <?php foreach (CriaturaRepositorio::ELEMENTOS as $opcao): ?>
                <option value="<?= escapar($opcao) ?>"
                    <?= $filtroElemento === $opcao ? 'selected' : '' ?>>
                    <?= escapar(strtoupper($opcao)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtros__acoes">
        <button type="submit" class="botao botao--pequeno">APLICAR</button>
        <a href="<?= escapar(url('/criaturas/listar.php')) ?>" class="botao botao--pequeno botao--secundario">LIMPAR</a>
    </div>
</form>

<?php if (empty($criaturas)): ?>
    <div class="estado-vazio">
        <pre class="estado-vazio__arte" aria-hidden="true">
[ NENHUMA_AMEAÇA_REGISTRADA ]

   /\___/\
  ( o   o )
   >  ^  <
   /     \
  /_______\
        </pre>
        <p class="estado-vazio__texto">
            Os arquivos do Bestiario estao silenciosos. Clique em
            <a href="<?= escapar(url('/criaturas/formulario.php')) ?>" class="link">[NOVA CRIATURA]</a>
            para registrar a primeira ameaca.
        </p>
    </div>
<?php else: ?>
    <div class="galeria" role="list">
        <?php foreach ($criaturas as $criatura):
            $slug    = strtolower((string) $criatura['elemento']);
            $fotoUrl = UploadHelper::urlImagem('criaturas', $criatura['foto_arquivo'] ?? null);
        ?>
            <article class="cartao-criatura cartao-criatura--<?= escapar($slug) ?>" role="listitem">
                <header class="cartao-criatura__topo">
                    <span>// VD <?= str_pad((string) (int) $criatura['vd'], 2, '0', STR_PAD_LEFT) ?></span>
                    <svg class="tag-elemento tag-elemento--<?= escapar($slug) ?> cartao-criatura__elemento"
                         role="img"
                         aria-label="Elemento <?= escapar((string) $criatura['elemento']) ?>"
                         focusable="false">
                        <title><?= escapar((string) $criatura['elemento']) ?></title>
                        <use href="#el-<?= escapar($slug) ?>"></use>
                    </svg>
                </header>

                <?php if ($fotoUrl): ?>
                    <img src="<?= escapar($fotoUrl) ?>" alt="" class="cartao-criatura__foto">
                <?php endif; ?>

                <h2 class="cartao-criatura__nome">
                    <a href="<?= escapar(url('/criaturas/visualizar.php?id=' . (int) $criatura['id'])) ?>"
                       class="cartao-npc__nome-link"><?= escapar($criatura['nome']) ?></a>
                </h2>

                <dl class="cartao-criatura__stats">
                    <div class="cartao-criatura__stat">
                        <dt>VD</dt>
                        <dd><?= escapar((string) (float) $criatura['vd']) ?></dd>
                    </div>
                    <div class="cartao-criatura__stat">
                        <dt>PV</dt>
                        <dd>
                            <?= (int) $criatura['pv_atual'] ?> / <?= (int) $criatura['pv_maximo'] ?>
                        </dd>
                    </div>
                </dl>

                <p class="cartao-criatura__habilidades">
                    <?= nl2br(escapar(mb_strimwidth((string) $criatura['habilidades'], 0, 220, '...'))) ?>
                </p>

                <footer class="cartao-criatura__acoes">
                    <a href="<?= escapar(url('/criaturas/formulario.php?id=' . (int) $criatura['id'])) ?>"
                       class="botao botao--pequeno">EDITAR</a>
                    <form action="<?= escapar(url('/criaturas/excluir.php')) ?>" method="POST"
                          data-confirmar="Confirma a exclusao da criatura <?= escapar($criatura['nome']) ?>?"
                          class="formulario-inline">
                        <input type="hidden" name="csrf_token" value="<?= escapar(gerarTokenCsrf()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $criatura['id'] ?>">
                        <button type="submit" class="botao botao--pequeno botao--perigo">EXPURGAR</button>
                    </form>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../views/rodape.php'; ?>
