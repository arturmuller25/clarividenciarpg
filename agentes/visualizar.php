<?php
declare(strict_types=1);

/**
 * Ficha de Agente em modo LEITURA — formato amigável para impressão e
 * consulta rápida durante a sessão. Para edição, vai para formulario.php.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/AgenteRepositorio.php';
require_once __DIR__ . '/../src/PericiaCatalog.php';
require_once __DIR__ . '/../src/CampanhaRepositorio.php';
require_once __DIR__ . '/../src/UploadHelper.php';

iniciarSessao();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    definirFlash('erro', 'Identificador de agente inválido.');
    header('Location: ' . url('/agentes/listar.php'));
    exit;
}

try {
    $repo  = new AgenteRepositorio();
    $ficha = $repo->buscarFichaCompleta($id);
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao carregar ficha: ' . $e->getMessage());
    header('Location: ' . url('/agentes/listar.php'));
    exit;
}

if ($ficha === null) {
    definirFlash('aviso', "Agente #{$id} não foi localizado.");
    header('Location: ' . url('/agentes/listar.php'));
    exit;
}

$a = $ficha['agente'];
$fotoUrl = UploadHelper::urlImagem('agentes', $a['foto_arquivo'] ?? null);

$campanhaNome = '';
if (!empty($a['campanha_id'])) {
    $campanha = (new CampanhaRepositorio())->buscarPorId((int) $a['campanha_id']);
    $campanhaNome = $campanha['nome'] ?? '';
}

$pvPct  = $a['pv_maximo']  > 0 ? round(($a['pv_atual']  / $a['pv_maximo'])  * 100) : 0;
$sanPct = $a['san_maximo'] > 0 ? round(($a['san_atual'] / $a['san_maximo']) * 100) : 0;
$pePct  = $a['pe_maximo']  > 0 ? round(($a['pe_atual']  / $a['pe_maximo'])  * 100) : 0;

$siglaAtrib = ['forca'=>'FOR','agilidade'=>'AGI','intelecto'=>'INT','vigor'=>'VIG','presenca'=>'PRE'];

$titulo      = 'FICHA_' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
$paginaAtiva = 'agentes';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        FICHA #<?= str_pad((string) $a['id'], 4, '0', STR_PAD_LEFT) ?>
    </h1>
    <div class="cabecalho-pagina__acoes">
        <a href="<?= escapar(url('/agentes/formulario.php?id=' . (int) $a['id'])) ?>"
           class="botao botao--primario">EDITAR FICHA</a>
        <a href="<?= escapar(url('/agentes/listar.php')) ?>" class="botao botao--secundario">VOLTAR</a>
        <form action="<?= escapar(url('/agentes/excluir.php')) ?>" method="POST"
              data-confirmar="Confirma o arquivamento permanente do agente <?= escapar($a['nome']) ?>?"
              class="formulario-inline">
            <input type="hidden" name="csrf_token" value="<?= escapar(gerarTokenCsrf()) ?>">
            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <button type="submit" class="botao botao--perigo">ARQUIVAR</button>
        </form>
    </div>
</section>

<article class="ficha-leitura">
    <header class="ficha-leitura__cabecalho">
        <?php if ($fotoUrl): ?>
            <img src="<?= escapar($fotoUrl) ?>" alt="" class="ficha-leitura__foto">
        <?php else: ?>
            <div class="ficha-leitura__foto ficha-leitura__foto--vazia" aria-hidden="true">&#9678;</div>
        <?php endif; ?>
        <div class="ficha-leitura__id">
            <h2 class="ficha-leitura__nome"><?= escapar((string) $a['nome']) ?></h2>
            <p class="ficha-leitura__sub">
                <?= escapar((string) $a['classe']) ?> · NEX <?= (int) $a['nex'] ?>%
                <?php if ($a['origem']): ?> · <?= escapar((string) $a['origem']) ?><?php endif; ?>
            </p>
            <?php if ($a['jogador']): ?>
                <p class="ficha-leitura__sub">JOGADOR: <strong><?= escapar((string) $a['jogador']) ?></strong></p>
            <?php endif; ?>
            <?php if ($campanhaNome): ?>
                <p class="ficha-leitura__sub ficha-leitura__campanha">
                    // CAMPANHA: <?= escapar($campanhaNome) ?>
                </p>
            <?php endif; ?>
        </div>
    </header>

    <section class="ficha-leitura__bloco">
        <h3>BARRAS VITAIS</h3>
        <div class="barras-agente barras-agente--leitura">
            <div class="barra-agente barra-agente--pv">
                <span class="barra-agente__rotulo">PV</span>
                <div class="barra-agente__trilho">
                    <div class="barra-agente__preenchimento" style="width: <?= $pvPct ?>%"></div>
                </div>
                <span class="barra-agente__valor"><?= (int) $a['pv_atual'] ?>/<?= (int) $a['pv_maximo'] ?></span>
            </div>
            <div class="barra-agente barra-agente--san">
                <span class="barra-agente__rotulo">SAN</span>
                <div class="barra-agente__trilho">
                    <div class="barra-agente__preenchimento" style="width: <?= $sanPct ?>%"></div>
                </div>
                <span class="barra-agente__valor"><?= (int) $a['san_atual'] ?>/<?= (int) $a['san_maximo'] ?></span>
            </div>
            <div class="barra-agente barra-agente--pe">
                <span class="barra-agente__rotulo">PE</span>
                <div class="barra-agente__trilho">
                    <div class="barra-agente__preenchimento" style="width: <?= $pePct ?>%"></div>
                </div>
                <span class="barra-agente__valor"><?= (int) $a['pe_atual'] ?>/<?= (int) $a['pe_maximo'] ?></span>
            </div>
        </div>
    </section>

    <section class="ficha-leitura__bloco">
        <h3>ATRIBUTOS</h3>
        <div class="grade-atributos grade-atributos--leitura">
            <?php foreach (['forca','agilidade','intelecto','vigor','presenca'] as $at): ?>
                <div class="atributo">
                    <span class="atributo__sigla"><?= $siglaAtrib[$at] ?></span>
                    <span class="atributo__valor"><?= (int) $a[$at] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="ficha-leitura__bloco">
        <h3>DEFESA</h3>
        <dl class="ficha-leitura__defesa">
            <div><dt>DEFESA</dt><dd><?= (int) $a['defesa'] ?></dd></div>
            <div><dt>PE/TURNO</dt><dd><?= (int) $a['pe_por_turno'] ?></dd></div>
            <div><dt>DESLOC.</dt><dd><?= (int) $a['deslocamento'] ?>m</dd></div>
        </dl>
        <?php if ($a['resistencias']): ?>
            <h4>RESISTÊNCIAS</h4>
            <p class="ficha-leitura__texto"><?= nl2br(escapar((string) $a['resistencias'])) ?></p>
        <?php endif; ?>
        <?php if ($a['proficiencias']): ?>
            <h4>PROFICIÊNCIAS</h4>
            <p class="ficha-leitura__texto"><?= nl2br(escapar((string) $a['proficiencias'])) ?></p>
        <?php endif; ?>
    </section>

    <?php
    $temNarrativa = !empty($a['aparencia']) || !empty($a['personalidade'])
                 || !empty($a['historico']) || !empty($a['objetivos']);
    if ($temNarrativa): ?>
        <section class="ficha-leitura__bloco">
            <h3>NARRATIVA</h3>
            <?php foreach (['aparencia'=>'APARÊNCIA','personalidade'=>'PERSONALIDADE',
                            'historico'=>'HISTÓRICO','objetivos'=>'OBJETIVOS'] as $k => $rotulo): ?>
                <?php if (!empty($a[$k])): ?>
                    <h4><?= $rotulo ?></h4>
                    <p class="ficha-leitura__texto"><?= nl2br(escapar((string) $a[$k])) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if (!empty($ficha['pericias'])): ?>
        <section class="ficha-leitura__bloco">
            <h3>PERÍCIAS TREINADAS</h3>
            <ul class="ficha-leitura__pericias">
                <?php foreach ($ficha['pericias'] as $nome => $info):
                    if (($info['grau'] ?? 'Destreinado') === 'Destreinado' && (int) $info['bonus_extra'] === 0) continue;
                ?>
                    <li>
                        <strong><?= escapar((string) $nome) ?></strong>
                        — <?= escapar((string) $info['grau']) ?>
                        <?php if ((int) $info['bonus_extra'] !== 0): ?>
                            (<?= ((int) $info['bonus_extra'] >= 0 ? '+' : '') . (int) $info['bonus_extra'] ?>)
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if (!empty($ficha['ataques'])): ?>
        <section class="ficha-leitura__bloco">
            <h3>ATAQUES</h3>
            <ul class="ficha-leitura__ataques">
                <?php foreach ($ficha['ataques'] as $at):
                    $atrib = $a[$at['atributo_base']] ?? 0;
                    $total = (int) $atrib + (int) $at['bonus_arma'] + (int) $at['bonus_extra'];
                ?>
                    <li>
                        <strong><?= escapar((string) $at['nome']) ?></strong>
                        — <?= ($total >= 0 ? '+' : '') . $total ?>
                        (<?= escapar($siglaAtrib[$at['atributo_base']] ?? '?') ?> <?= (int) $atrib ?>
                        + arma <?= (int) $at['bonus_arma'] ?>
                        + extra <?= (int) $at['bonus_extra'] ?>)
                        — Dano: <?= escapar((string) $at['dano']) ?>
                        <?php if ($at['tipo_dano']): ?>(<?= escapar((string) $at['tipo_dano']) ?>)<?php endif; ?>
                        <?php if ($at['descricao']): ?>
                            <br><small><?= escapar((string) $at['descricao']) ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if (!empty($ficha['inventario'])):
        $totalEspacos = 0.0;
        foreach ($ficha['inventario'] as $it) {
            $totalEspacos += (float) $it['espacos'] * (int) $it['quantidade'];
        }
    ?>
        <section class="ficha-leitura__bloco">
            <h3>INVENTÁRIO <small>(<?= number_format($totalEspacos, 2) ?> espaços)</small></h3>
            <ul class="ficha-leitura__inventario">
                <?php foreach ($ficha['inventario'] as $it): ?>
                    <li>
                        <strong><?= escapar((string) $it['nome']) ?></strong>
                        × <?= (int) $it['quantidade'] ?>
                        <?php if ($it['categoria']): ?>· <?= escapar((string) $it['categoria']) ?><?php endif; ?>
                        · <?= number_format((float) $it['espacos'], 2) ?> esp.
                        <?php if ($it['equipado']): ?>
                            <span class="tag-equipado">[EQUIPADO]</span>
                        <?php endif; ?>
                        <?php if ($it['descricao']): ?>
                            <br><small><?= escapar((string) $it['descricao']) ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if (!empty($ficha['rituais'])): ?>
        <section class="ficha-leitura__bloco">
            <h3>RITUAIS</h3>
            <ul class="ficha-leitura__rituais">
                <?php foreach ($ficha['rituais'] as $r):
                    $elemSlug = strtolower((string) $r['elemento']);
                ?>
                    <li class="ritual-leitura ritual-leitura--<?= escapar($elemSlug) ?>">
                        <strong><?= escapar((string) $r['nome']) ?></strong>
                        <span>Círculo <?= (int) $r['circulo'] ?>º · <?= escapar((string) $r['elemento']) ?>
                              · <?= (int) $r['custo_pe'] ?> PE</span>
                        <?php if ($r['descricao']): ?>
                            <p><?= nl2br(escapar((string) $r['descricao'])) ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</article>

<?php require __DIR__ . '/../views/rodape.php'; ?>
