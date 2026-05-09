# Registro de Sessão (Logs)

**Data:** 08 de maio de 2026
**Objetivo:** Rastreamento de eventos, comunicações e ajustes realizados no projeto.

## Entradas e Eventos

### 08/05/2026

#### Manhã — Setup e fundações
- **09h15**: Início da sessão. Levantamento dos requisitos e fluxo de dados do sistema.
- **09h30**: Discussão sobre os padrões de documentação com base nas práticas de arquivos de registro de arquitetura (ADRs) [Registro de decisão de arquitetura (ADR)](https://github.com).
- **09h45**: Alteração de dependências e configuração do ambiente local.
- **10h05**: Criação dos arquivos `logs.md` e `decisoes.md` para melhor rastreabilidade.

#### Tarde — Reforma VTT (sessão Claude)

##### Sprint 1 — Importação e onboarding
- Importado o repositório `arturmuller25/clarividenciarpg` (via download ZIP, pois `git` não estava instalado).
- Scan do estado inicial: MVP de NPC/Bestiário/Rolagem/Histórico já estava bem implementado.
- Gaps identificados contra `context.md`: dashboard estático, NPC sem busca, NPC sem ficha individual.
- Implementado: busca textual no `NpcRepositorio` (LIKE com escape de curingas), página `npcs/visualizar.php` (dossiê individual), dashboard com indicadores dinâmicos.

##### Sprint 2 — XAMPP e portabilidade
- Configurada a pasta `C:\xampp\htdocs\clarividenciarpg\` para ser servida pelo Apache.
- **Decisão 003**: helper `url()` em `config.php` que detecta automaticamente o sub-diretório de instalação. Refatorados ~20 arquivos para usar `url(...)` ao invés de caminhos absolutos hardcoded.

##### Sprint 3 — Estética e Splash
- **Decisão 004**: tipografia em três camadas (Cinzel/Montserrat/Helvetica) substituindo a antiga monoespaçada exclusiva. JetBrains Mono preservada para IDs/timestamps.
- **Decisão 005**: menu hambúrguer pure-CSS (checkbox + label + glassmorphism), sempre visível em todas as resoluções.
- **Decisão 006**: Hero/Splash com d20 SVG rolando + título "Clarividência Paranormal" deslizando da direita. Inicialmente exibido apenas uma vez por sessão (sessionStorage).
- **Decisão 007**: sistema multi-dado (d4/d6/d8/d10/d12/d20/d100) preservando regra Ordem Paranormal para d20. Migration 002 criada.
- Migration 002 falhou inicialmente em MariaDB (XAMPP não usa MySQL); reescrita usando `DROP TABLE` + `CREATE TABLE` ao invés de `ALTER TABLE ... DROP CHECK`.

##### Sprint 4 — Áudio e refinamentos
- Áudio integrado: `som_para_a_hero.mp3` sincronizado com início da rotação do d20; `som_para_as_rolagens.mp3` em cada rolagem com fadeOut.
- Política de autoplay tratada com `try/catch` na promise do `.play()`. Fallback: botão "CLIQUE PARA INICIAR O RITUAL".
- **Reversão da Decisão 006**: Hero passou a rodar em **toda** recarga (F5), removendo a flag `sessionStorage`.
- Animação do título refinada para `ease-in-out` com `opacity 0→1` + `blur 14px→0` + `letter-spacing 0.5em→0.18em` (vibe fantasmagórica).
- Cooldown no botão "Invocar Rolagem" (1.3s) para impedir spam que bagunçava áudio + visual.

##### Sprint 5 — Reforma VTT (Fase 1)
- **Decisão 008**: a "Reforma VTT" foi planejada em **5 fases iterativas** (não atacar tudo num turno só).
- **Fase 1 entregue**: Migration 003 com 6 tabelas novas (campanhas, agentes, agente_pericias, agente_ataques, agente_inventario, agente_rituais) + ALTER em npcs/criaturas. Refresh visual obsidiana `#0a0a0a` + variáveis `--metalico*` + `--glow-*`. Ícones geométricos dos dados (SVG sprite). Estrutura `/uploads/` com `.htaccess` bloqueando exec PHP/CGI.

##### Sprint 6 — Hero 2.0 + Campanhas (Fase 2)
- Hero d20 com **perspective + rotate3d**: 11 faces visíveis com 3 níveis de sombreamento (claro/médio/escuro), 8 linhas de aresta, camada especular. Easing realista em 3 fases (aceleração / linear / desaceleração com overshoot).
- 3 ondas de choque concêntricas no impacto (1ª dourada, 2ª vermelho-Sangue, 3ª flash radial).
- CRUD completo de Campanhas: galeria com cards de capa 16:9, formulário com upload, exclusão com cleanup do arquivo físico.
- `UploadHelper.php` criado: validação MIME via `finfo`, whitelist de extensões (jpg/png/webp), nome final aleatório (`bin2hex(random_bytes(8))`), proteção contra path traversal.

##### Sprint 7 — Acabamento (Fase 3)
- Bug crítico identificado: atributo HTML `hidden` em `<g>` SVG não é universalmente respeitado pelos browsers. Tripla camada de defesa: atributo `hidden` + atributo `display="none"` + classe CSS `.is-oculta`.
- Marca renomeada: `TERMINAL_DA_ORDEM` → **"Clarividência Paranormal"** (com "Paranormal" em dourado + glow).
- **Decisão 010**: paleta refinada incluindo elemento **Medo** (`#5a1d8a`) — usado como gradiente sutil no fundo do site (purple void no canto superior esquerdo + sangue no inferior direito).
- NPCs e Criaturas agora podem ser vinculados a uma campanha (dropdown nos formulários, FK `campanha_id`).
- Seção mínima de Agentes (apenas leitura, com aviso "CRUD na Fase 4").
- Responsividade mobile completa: 4 breakpoints (1024 / 768 / 540 / 380). Tabela do histórico vira cards no mobile via `data-label`.

##### Sprint 8 — Ficha de Agente (Fase 4)
- **Decisão 011**: layout em **página única longa** com `<details>/<summary>` nativos (não tabs). Suporta impressão. JS-free na navegação entre seções.
- 9 partials criados em `agentes/partials/` (identidade, barras, atributos, defesa, narrativa, perícias, ataques, inventário, rituais).
- `AgenteRepositorio` com **salvamento transacional** (BEGIN / COMMIT / ROLLBACK) das tabelas filhas. Estratégia DELETE + INSERT para perícias/ataques/inventário/rituais.
- `AgenteValidador` com normalização de listas (skip linhas vazias) e clamps de range.
- `assets/js/agente.js`:
  - Barras PV/SAN/PE atualizam visualmente em tempo real (preenchimento via CSS transition de 380ms).
  - Cálculo automático de ataques: `[Atributo] + [Bônus arma] + [Bônus extra]` exibido a cada digit. Mudar atributo na seção 03 recalcula TODOS os ataques.
  - Total de espaços do inventário somado live.
  - Listas dinâmicas via `<template>` HTML5 nativo (adicionar/remover linhas).
  - Cor da perícia varia com grau (Especialista ganha glow dourado).
  - Cor do ritual varia com elemento selecionado.
- Página `agentes/visualizar.php` em modo leitura (impressão-friendly).

##### Sprint 9 — Publicação no GitHub
- `git` não estava instalado no sistema. Instalado via `winget install Git.Git` (versão 2.54).
- **Decisão 012**: estratégia de publicação preservando histórico do remoto:
  1. Clone do repo existente para `$env:TEMP\clarividenciarpg-deploy\`.
  2. Copiar todos os arquivos atuais por cima (preservando `.git`).
  3. `.gitignore` atualizado para ignorar `uploads/{campanhas,agentes,npcs,criaturas}/*` mas preservar a estrutura via `.gitkeep`.
  4. Configurado autor: `arturmuller25 <claudetimave@gmail.com>`.
  5. Commit único (`9ec0fe7`): 66 arquivos, +7.225 linhas, −230 linhas.
  6. `git push` automático via Git Credential Manager (autenticação OAuth via navegador na primeira vez, sem necessidade de inserir token manualmente).
- Confirmação via API pública do GitHub: `9ec0fe7` é agora o `HEAD` do `main` no repo `arturmuller25/clarividenciarpg`.

##### Sprint 10 — Documentação para continuidade
- `claude.md` expandido para guia master de continuação (arquitetura, paths, convenções, backlog, deploy, GitHub).
- `logs.md` (este arquivo) atualizado com cronologia completa de todos os sprints.
- Decisões 010, 011, 012 adicionadas a `decisoes.md`.

##### Sprint 11 — Polimento estético + acentos + fotos universais
- **Bug crítico do SVG `<g hidden>`**: identificado que browsers não respeitam o atributo HTML `hidden` em elementos SVG `<g>` de forma confiável. Tripla camada de defesa: atributo `hidden` + atributo SVG `display="none"` + classe CSS `.is-oculta` com `!important`. Forma errada não vaza mais.
- **Marca renomeada**: `TERMINAL_DA_ORDEM` → "Clarividência **Paranormal**" (com "Paranormal" em dourado + glow). Footer atualizado para v0.2.
- **Decisão 015**: auditoria de acentos em todos os textos visíveis (HISTÓRICO, BESTIÁRIO, DOSSIÊ, AMEAÇA, LOCALIZAÇÃO, PERCEPÇÃO, INVOCAÇÃO, etc.). Validadores também tiveram suas mensagens de erro corrigidas.
- **Críticos somente no d20** (gate em dados.js E rolagem/api.php) — para outros tipos, mesmo se cair 20 ou 1, não há flag de crítico/desastre.
- **Adicionado elemento "Medo"** (`#5a1d8a`) à paleta como gradiente sutil no fundo do site.
- **Fotos universalizadas**: NPCs e Criaturas agora têm upload de foto + exibição em listagem (thumbnail 1:1) e perfil (foto 140×140). Criada `criaturas/visualizar.php` (não existia). Repositórios atualizados para preservar `foto_arquivo` quando a chave não vem no payload (proteção contra apagar foto numa edição).
- **Cropper 1:1 v1** (`assets/js/cropper.js`): primeira tentativa baseada em `<img>` + `transform`. **Tinha bugs** — imagem flutuava fora do palco, máscara não escurecia bordas.

##### Sprint 12 — Cropper canvas + fix .htaccess (HTTP 500)
- Usuário reportou: "imagem fica solta na tela, podendo ser mexida pra qualquer lugar do site" + "fotos não aparecem mesmo após upload".
- **Diagnóstico**: `.htaccess` da pasta `/uploads/` retornava **HTTP 500 em todas as URLs**. Log do Apache (`C:\xampp\apache\logs\error.log`) mostrou `<FilesMatch> was not closed at line 18` — o arquivo tinha as 4 linhas de `ForceType` no formato compacto (abrir e fechar `<FilesMatch>` na mesma linha), o que Apache 2.4 não tolera. Os arquivos físicos e os `foto_arquivo` no banco sempre estiveram corretos — o Apache só recusava servir.
- **Decisão 017**: reescrever `.htaccess` com cada `<FilesMatch>` em três linhas. Confirmação: URLs voltaram a HTTP 200 com Content-Type correto.
- **Decisão 016**: reescrever cropper de zero em **canvas-only**. A imagem agora não existe como elemento DOM — só como pixels desenhados num `<canvas 320×320>`. Estado simples: `{ img, scale, offsetX, offsetY }`. Drag move offset, slider/wheel altera escala, redesenha. `clampOffsets()` impede mostrar bordas pretas além da imagem. Submit gera File JPEG 800×800 q=0.9 via `toBlob` e substitui `input.files` via `DataTransfer`.

##### Sprint 13 — Hero respeitando navegação interna
- Usuário reportou que clicar em "VOLTAR AO PAINEL" disparava a Hero novamente — irritante em uso real.
- **Decisão 019**: gate via Performance Navigation API + sessionStorage:
  - Primeira visita → roda
  - F5/Ctrl+R → roda (mesmo com flag setada)
  - Link interno → pula
  - Botão voltar/avançar → pula
- Implementado em `hero.js` com fallback para a API legada (`performance.navigation.type`). Flag `terminalHeroVisto` em sessionStorage (zera ao fechar aba).

##### Sprint 14 — Multi-dado liberado + áudio em 3 níveis
- Brief: liberar multi-dado para todos os tipos (não só d20), exibir TODOS os valores em multi-não-d20, e tocar sons diferentes conforme a quantidade. 2 novos MP3s entregues pelo usuário: `som_para_rolagem_multipla.mp3` e `som_para_rolagem_com_muitos_dados.mp3`.
- **Decisão 020**: regra dual:
  - d20 mantém regra OP (vantagem/desastre)
  - d4..d100 com N>1 rola N independentes, todos exibidos, **resultadoFinal = SOMA**
- **Migration 004**: `resultado_final` de `TINYINT UNSIGNED CHECK 1..100` para `SMALLINT UNSIGNED CHECK 1..2000` — 10d100 cabe.
- Form `rolagem/index.php`: campo quantidade sempre visível; ajuda contextual via `[data-ajuda-d20]` vs `[data-ajuda-outros]`; min=0 só para d20.
- 1ª implementação de áudio escalonado: 1 som único escolhido por quantidade (1 dado / 2-4 / 5+).
- **Bug pós-refactor**: rolagem parou de executar — sem ticker, sem animação, sem fetch. **Decisão 022**: identificado bug de closure — `tocarAudioRolagem` definida no escopo da IIFE não enxergava `audioRolagemMulti` etc. declaradas dentro do `DOMContentLoaded`. ReferenceError silencioso em async handler engolia tudo. Fix: mover funções stateful para dentro do callback.

##### Sprint 15 — Áudio em camadas + calibragem + corte preciso
- Usuário pediu refinamento: (1) equalizar volumes dos 3 MP3, (2) som começar no clique e terminar exatamente quando o número aparece, (3) sons devem **disparar juntos** quando há multi-dado (sensação de coro).
- **Decisão 021**:
  - **Calibragem em JS** (não reencodar arquivos): constante `VOLUMES.*` no topo do `dados.js` (`rolagem: 0.55, multipla: 0.50, muitos: 0.50`). Ajustável a qualquer momento.
  - **Sons em camadas** (não substituem-se): 1 dado = som1; 2-4 dados = som1+som2; 5+ dados = som1+som2+som3. Função `tocarAudioRolagem` virou `tocarAudiosRolagem` (plural) que retorna array; estado `audioAtivo` virou `audiosAtivos` (array); fadeOut em loop sobre todos.
  - **FadeOut encurtado** de 450ms para 80ms: o som termina junto com o número aparecendo, sem arrastar. Fade rápido (em vez de corte seco) evita "click" digital de buffer cortado.

##### Sprint 16 — Documentação atualizada
- Auditoria geral dos `.md` para garantir que toda evolução desde a última doc-pass está registrada:
  - `context.md`: removidas limitações obsoletas (sem áudio, sem ficha de jogador) — agora ambas existem. Funcionalidades expandidas para refletir o estado v0.2 com 7 módulos. Adicionado elemento Medo na paleta. Stack atualizada com nota explícita de "zero dependências de runtime".
  - `decisoes.md`: 8 ADRs novos (015 a 022) cobrindo: auditoria de acentos, cropper canvas-only, bug do `.htaccess`, fotos universais, gate da Hero, multi-dado liberado, áudio em camadas, bug de closure.
  - `logs.md` (este arquivo): sprints 11-16 documentados.
  - `claude.md`: já estava atualizado, sem mudanças significativas necessárias.

---

### 08–09/05/2026 — Refatoração visual via Claude Design (sessão Phase 6)

##### Sprint 17 — Setup do design system + plano de integração
- Bug do HY093 em `AgenteRepositorio::inserirAgente` corrigido (commit `2e107c6`): o INSERT sempre incluía `:foto_arquivo` no SQL, mas `bindAgente()` só fazia bind se a key existia no array. Default `null` antes do bind resolveu sem alterar o caminho dinâmico do UPDATE.
- Tentativa de fetch do design system via URL `api.anthropic.com/v1/design/h/...` retornou 404 sem `WWW-Authenticate` — endpoint não responde para esse ID. Usuário extraiu o ZIP manualmente em `_design_import/clarivid-ncia-paranormal-design-system/`.
- Mapeamento dos 32 arquivos. **Descoberta crítica**: `diff -q` confirmou que `assets/terminal.css` deles é **idêntico** ao nosso — o "design system" é uma **curadoria documentada**, não uma reforma. O genuinamente novo: 6 SVGs (3 logos, d20-hero estático, dice sprite, elementos sprite), tokens novos no `colors_and_type.css`, paleta envenenada page-scoped no `index.html` do painel, hero standalone com geometria 3D real (487 linhas de JS).
- `INTEGRACAO_DESIGN.md` criado com plano em **7 passos sequenciais**, checklist de validação, riscos por componente, dependências, decisões D1-D5 registradas (commit `37f19cb`).

##### Sprint 18 — Passo 1: Logo stacked + Menu hambúrguer
- Bloco `.menu-hamburger__logo` adicionado no painel hambúrguer com 3 camadas: CLARIVIDÊNCIA fino (Cinzel 500, ls 0.62em) / PARANORMAL grande dourado com glow / icosaedro inline 22px (commit `2f21aca`).
- Estratégia HTML+SVG inline (texto selecionável, currentColor) em vez de `<object data="logo-stacked.svg">` — recomendada pelo `preview/logo.html` e `Header.jsx` do design system.
- Bug do círculo escuro flutuando + "MENU" solto + X com box quadrado destacado (Bug 2 do round de correções) corrigido depois no commit `db930a8`: `.menu-hamburger__painel::before/::after` decoradores órfãos removidos, `.menu-hamburger__rotulo` escondido quando `:checked`, borda do botão atenuada quando aberto, padding-top reduzido de 80px → 40px.

##### Sprint 19 — Passo 5: Paleta envenenada global + grain SVG + tokens novos
- Reforma direta no `:root` (Decisão D4/025): `--el-conhecimento` `#ffd60a→#e0b53d` (mostarda envelhecida), `--el-energia` `#9d4edd→#7b4d9e` (roxo cinza), `--el-sangue` `#c8102e→#a53846` (sangue antigo), `--critico` `#00ff66→#5fa873` (fosforescente apagado). Glows recalculados (commit `abef18b`).
- Tokens novos do `colors_and_type.css` adicionados como **acréscimo** ao `:root` (Decisão D3/027): spacing scale, type semantic shorthands, letter-spacing, shadow, animation easing.
- Background contínuo: 6 radial gradients fixos cobrindo o scroll inteiro (extraídos do `index.html` do painel). `body::before` substituído por SVG noise fractal inline base64 (em vez de scanlines lineares). Vignette ajustada para 38% transparente → 55% black.
- Decisão de adiar audit de glows decorativos para os Passos 6/7 — paleta envenenada por si reduz a intensidade percebida.

##### Sprint 20 — Passos 2/3/4/6/7 em batelada
- **Passo 2 (commit `efc584c`)** — `assets/img/elementos-icons.svg` copiado do design system. Sprite incluído via `readfile()` em `views/cabecalho.php`. `criaturas/listar.php` e `criaturas/visualizar.php` agora usam `<svg><use href="#el-X"/></svg>`. CSS `.tag-elemento` com 5 variantes + drop-shadow do glow envenenado. **Sem FontAwesome** (Decisão D1/024).
- **Passo 3 (commit `4232911`)** — `assets/img/dice-icons.svg` copiado renomeando IDs `dN→geo-dN` via PowerShell regex (Decisão D2/026 — preserva compat com `dados.js`). Sprite inline antigo de `rolagem/index.php` (50 linhas) substituído por `readfile()`. Outer `<text>` redundante removido (sprite novo já tem label dentro do `<symbol>`).
- **Passo 4 (commit `9af1eec`)** — `.cartao-criatura` refit: background `--metalico` (papel texturizado), border-left 4px, padding ajustado, sombra `--shadow-card`/`--shadow-lift` no hover. Tipografia hierárquica via tokens semânticos. Bug fix: classe `.cartao-npc__nome-link` (copy-paste leftover) corrigida para `.cartao-criatura__nome-link`.
- **Passo 6 (commit `1765f3e`)** — refator pesado do `index.php` em **5 seções cinematográficas**: `.painel-hero` (com glitch sutil em "T" + card `.ultima-critica`), `.faixa-stats` (5 tiles + sparkline 7d gerado em PHP), `.trilhas` (3 principais + 3 auxiliares), `.sussurros` (atmosférico, fontes Cormorant Garamond + IM Fell English), `.atalhos-rapidos`. Microcopy oculto `// nao confiar nos numeros pares`. `LogRepositorio::buscarUltimaCritica()` e `::contarPorDia(int)` adicionados. Cormorant Garamond + IM Fell English no `<link>` Google Fonts (Decisão D5/028).
- **Passo 7 v1 (commit `37cc563`)** — Hero D20 cinematográfica primeira versão: queda 4.6s + brilho dourado intenso 4 camadas + pool of light dourado-púrpura no chão.

##### Sprint 21 — Bug fixes pontuais
- **Bugs 1+2 (commit `db930a8`)**: "PAINEL DO MESTRE" quebrava em "MEST/RE" — `white-space: nowrap` + `font-size: clamp(2rem, 6vw, 5rem)` + `word-break: keep-all` + media query mobile. Menu hambúrguer com elementos antigos sobrepostos (4 sub-correções listadas no Sprint 18).
- **Bug 3 (commit `1e8ca4a`)**: easter egg `// nao confiar nos numeros pares` invisível por causa do stacking context da `.terminal` (z-index 1) capando a visibilidade. Movido para fora de `.terminal` (em `rodape.php`), z-index 100, opacity 0.30 com `--gold-dim`.
- **Refinamento (commit `de7ce7f`)**: 4 stat-tiles sem sparkline ganharam hint contextual diegético (`// EM_ANDAMENTO`, `// EM_CAMPO`, `// DOSSIES_ABERTOS`, `// CATALOGADAS` quando há dados; alternativas vazias quando 0).

##### Sprint 22 — Refinamento da Hero (sincronia áudio + loop ambiente + botões)
- **Sincronia 3.2s (commit `c9ba77f`)** — usuário trocou `som_para_a_hero.mp3` por uma versão de 320 kbps @ 48 kHz. MP3 header parseado: 127.724 bytes / 320 kbps = **3.19s**. Animação encurtada de 4.5s → 3.2s para casar com a duração real do som (T_SETTLE_END, retiming de shockwaves/floor/aura/título/subtítulo, `data-duracao-ms` 6500 → 5200).
- **Loop ambiente + botão "// ROMPER O VÉU" (commit `c935acd`)** — `clarividencia_paranormal_loop.mp3` (2.45 MB, 61.2s @ 320 kbps). `iniciarLoop()` em t=3.7s (sincronizado com title reveal). `fadeInLoop` 1s, `fadeOutLoopEFechar` 800ms ao clicar. Botão "// MANIFESTAR TRANSMISSÃO" (fallback de autoplay) substituído por "// ROMPER O VÉU" (CSS keyframe entra em 5.0s). `await audioQueda.play()` antes do primeiro RAF para sincronia frame-a-frame.
- **Trava scroll + Esc/wheel + diagnóstico autoplay (commit `f5acea3`)** — `body.hero-ativa { overflow: hidden }` (Decisão D030). Esc + wheel-down como acionadores alternativos do "// ROMPER O VÉU" após `_podeFinalizar` em 5.8s. Logs diagnósticos `[Hero] ...` adicionados.
- **Botão "// INICIAR" antes da animação (commit `b1fe3e5`)** — Decisão final D029. A tentativa anterior de "deixa rodar muda + libera no primeiro click qualquer" falhava para o áudio de queda (que dispara em t=0; quando o click chega, a queda já passou). Solução: gate de gesto humano antes da animação — dado em pose RESTING + bloco "// AUTORIZAR RITUAL" + botão "// INICIAR". Click libera autoplay para a sessão → animação dispara com áudio sincronizado frame-a-frame na primeira tentativa. Fluxo definitivo do produto.

##### Sprint 23 — Fechamento e documentação consolidada
- **decisoes.md** estendido com 8 ADRs novas (023-030) cobrindo a refatoração visual completa.
- **logs.md** (este arquivo) com sprints 17-23 cronológicos.
- **claude.md** com Phase 6 marcada concluída, Phase 7 backlog atualizado, tabela "Onde olhar" expandida com novas entradas (gate de autoplay, geometria 3D do icosaedro, sprite de dados global, trava de scroll da Hero).
- **INTEGRACAO_DESIGN.md** marcado com seção Conclusão (totais de commits e LOC), preservado no repo como artefato histórico.
- **`_design_import/`** comprimido em ZIP arquivado em `~/Downloads/clarividencia-design-archive-2026-05-09.zip` (caso seja necessário consultar SVG/HTML originais no futuro), pasta deletada do XAMPP.
- Push final pra GitHub depois de tudo aplicado.

---

## Como retomar em uma nova sessão

1. **Leia primeiro**: `claude.md` (guia master).
2. **Verifique o estado**: `git log --oneline` no clone, `git status` para ver mudanças não publicadas.
3. **Veja o que falta**: seção "Backlog" do `claude.md`.
4. **Histórico de decisões**: `decisoes.md`.
5. **Cronologia detalhada**: este arquivo (`logs.md`).
