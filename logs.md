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

## Como retomar em uma nova sessão

1. **Leia primeiro**: `claude.md` (guia master).
2. **Verifique o estado**: `git log --oneline` no clone, `git status` para ver mudanças não publicadas.
3. **Veja o que falta**: seção "Backlog" do `claude.md`.
4. **Histórico de decisões**: `decisoes.md`.
5. **Cronologia detalhada**: este arquivo (`logs.md`).
