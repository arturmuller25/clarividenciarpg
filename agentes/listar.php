<?php
declare(strict_types=1);

/**
 * Galeria de Agentes (personagens jogadores).
 *
 * Estado atual (Fase 2): apenas leitura. CRUD completo virá na Fase 4
 * (ficha de agente com Identidade, Atributos, Barras dinâmicas, Perícias,
 * Ataques, Inventário, Rituais, Narrativa).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/sessao.php';
require_once __DIR__ . '/../src/AgenteRepositorio.php';
require_once __DIR__ . '/../src/UploadHelper.php';

iniciarSessao();

$filtroClasse    = isset($_GET['classe'])      ? (string) $_GET['classe']      : '';
$filtroCampanha  = isset($_GET['campanha_id']) && ctype_digit((string) $_GET['campanha_id'])
                    ? (int) $_GET['campanha_id'] : null;

try {
    $repo    = new AgenteRepositorio();
    $agentes = $repo->listar([
        'classe'      => $filtroClasse,
        'campanha_id' => $filtroCampanha,
    ]);
} catch (Throwable $e) {
    definirFlash('erro', 'Falha ao consultar agentes: ' . $e->getMessage());
    $agentes = [];
}

$titulo      = 'AGENTES';
$paginaAtiva = 'agentes';
require __DIR__ . '/../views/cabecalho.php';
?>

<section class="cabecalho-pagina">
    <h1 class="cabecalho-pagina__titulo">
        <span class="cabecalho-pagina__prompt">&gt;</span>
        AGENTES DA ORDEM
    </h1>
    <p class="cabecalho-pagina__subtitulo">
        FICHAS DE PERSONAGEM JOGADOR (PJ) — não confundir com NPCs.
        TOTAL ATIVO: <strong><?= count($agentes) ?></strong>
    </p>
    <div class="cabecalho-pagina__acoes">
        <a href="<?= escapar(url('/agentes/formulario.php')) ?>" class="botao botao--primario">
            <span aria-hidden="true">+</span> NOVO AGENTE
        </a>
        <a href="<?= escapar(url('/index.php')) ?>" class="botao botao--secundario">VOLTAR AO PAINEL</a>
    </div>
</section>

<?php if (empty($agentes)): ?>
    <div class="estado-vazio">
        <pre class="estado-vazio__arte" aria-hidden="true">
[ NENHUM_AGENTE_REGISTRADO ]

   _________
  |  ID:    |
  | -- --   |
  | -- --   |
  | _______ |
  |/_______\|
        </pre>
        <p class="estado-vazio__texto">
            Nenhum agente foi cadastrado nos arquivos da Ordem.
            <br>A ficha completa (atributos, perícias, inventário, rituais) será
            disponibilizada na próxima atualização do sistema.
        </p>
    </div>
<?php else: ?>
    <div class="galeria">
        <?php foreach ($agentes as $a):
            $foto = UploadHelper::urlImagem('agentes', $a['foto_arquivo'] ?? null);
            $pvPct  = $a['pv_maximo']  > 0 ? round(($a['pv_atual']  / $a['pv_maximo'])  * 100) : 0;
            $sanPct = $a['san_maximo'] > 0 ? round(($a['san_atual'] / $a['san_maximo']) * 100) : 0;
            $pePct  = $a['pe_maximo']  > 0 ? round(($a['pe_atual']  / $a['pe_maximo'])  * 100) : 0;
        ?>
            <article class="cartao-agente">
                <div class="cartao-agente__topo">
                    <span class="cartao-agente__id">#<?= str_pad((string) $a['id'], 4, '0', STR_PAD_LEFT) ?></span>
                    <span class="cartao-agente__classe"><?= escapar((string) $a['classe']) ?> · NEX <?= (int) $a['nex'] ?>%</span>
                </div>
                <h2 class="cartao-agente__nome">
                    <a href="<?= escapar(url('/agentes/visualizar.php?id=' . (int) $a['id'])) ?>"
                       class="cartao-npc__nome-link"><?= escapar((string) $a['nome']) ?></a>
                </h2>
                <?php if (!empty($a['jogador'])): ?>
                    <p class="cartao-agente__jogador">JOGADOR: <strong><?= escapar((string) $a['jogador']) ?></strong></p>
                <?php endif; ?>
                <?php if (!empty($a['campanha_nome'])): ?>
                    <p class="cartao-agente__campanha">// <?= escapar((string) $a['campanha_nome']) ?></p>
                <?php endif; ?>

                <div class="barras-agente">
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

                <footer class="cartao-agente__acoes">
                    <a href="<?= escapar(url('/agentes/visualizar.php?id=' . (int) $a['id'])) ?>"
                       class="botao botao--pequeno botao--primario">FICHA</a>
                    <a href="<?= escapar(url('/agentes/formulario.php?id=' . (int) $a['id'])) ?>"
                       class="botao botao--pequeno">EDITAR</a>
                    <form action="<?= escapar(url('/agentes/excluir.php')) ?>" method="POST"
                          data-confirmar="Confirma o arquivamento permanente do agente <?= escapar($a['nome']) ?>?"
                          class="formulario-inline">
                        <input type="hidden" name="csrf_token" value="<?= escapar(gerarTokenCsrf()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button type="submit" class="botao botao--pequeno botao--perigo">ARQUIVAR</button>
                    </form>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../views/rodape.php'; ?>
