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

---

## Como retomar em uma nova sessão

1. **Leia primeiro**: `claude.md` (guia master).
2. **Verifique o estado**: `git log --oneline` no clone, `git status` para ver mudanças não publicadas.
3. **Veja o que falta**: seção "Backlog" do `claude.md`.
4. **Histórico de decisões**: `decisoes.md`.
5. **Cronologia detalhada**: este arquivo (`logs.md`).
