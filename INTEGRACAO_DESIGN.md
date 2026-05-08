# Integração do Design System — Clarividência Paranormal

**Data de início:** 2026-05-08
**Branch:** `main`
**Estado de partida:** commit `213078d` (XAMPP, clone e GitHub em paridade)
**Origem do design:** novo design system gerado no Claude Design
**Tipo de mudança:** refatoração visual + componentes novos (regras do RPG e contratos de dados não mudam)

> Este arquivo é o **espelho** da integração. Cada componente tem checklist
> de status, arquivos afetados, riscos e ordem de execução. Decisões não-óbvias
> que emergirem durante a integração ficam registradas na seção
> "Decisões durante a integração" abaixo e depois migram para `decisoes.md`
> como ADR 023+.

---

## Componentes recebidos (9)

1. **Logo redesenhada (stacked)** — "CLARIVIDÊNCIA" branco fino + letter-spacing largo / "PARANORMAL" dourado bold / ícone do icosaedro hexagonal abaixo.
2. **Hero do D20 refinada** — animação de queda 4–5s com easing realista, brilho dourado em 20, título aparece após o dado parar.
3. **Painel do Mestre reformulado** — 5 seções cinematográficas: hero do dashboard com card "última atividade crítica", faixa de stats com sparklines, trilhas de investigação com hierarquia, "Sussurros do Outro Lado" atmosférico, atalhos rápidos.
4. **Cards de elementos** — overflow do "CONHECIMENTO" corrigido + uso documentado.
5. **Ícones geométricos dos dados (D4/D6/D8/D10/D12/D20/D100)** — só com contorno externo, sem linhas internas.
6. **Ícones FontAwesome 6 para tags de elemento** — gota (Sangue), caveira (Morte), olho (Conhecimento), raio (Energia), vórtice (Medo) — substituem labels textuais nos cards de criatura.
7. **Background contínuo** — sem cortes entre seções, grain SVG overlay, transições suaves via mask.
8. **Menu hambúrguer com logo stacked** — agora exibe a logo redesenhada no topo do painel.
9. **Refinamentos estéticos gerais** — paleta "envenenada" (mostarda em vez de neon, roxo dessaturado, vermelho de sangue antigo), redução drástica de glows decorativos, assimetrias intencionais, microcopy refinado, easter egg `// não confiar nos números pares`.

---

## Avaliação crítica da ordem proposta

A ordem original (8 passos, do isolado ao invasivo) é sólida. Sugiro **2 ajustes**:

### Ajuste 1 — Combinar "Logo" (passo 1) com "Menu hambúrguer" (passo 5)

Ambos editam `views/cabecalho.php` (topbar e painel hambúrguer compartilham o arquivo) e a logo stacked é a mesma marca em dois pontos do mesmo arquivo. Separar em duas etapas dobra o ciclo de teste no mesmo arquivo sem ganho. Mesclar reduz 8 → 7 passos e garante consistência (o brand do topo e o brand do painel ficam idênticos no mesmo commit).

### Ajuste 2 — Mover "Background contínuo + refinamentos" para ANTES do "Painel do Mestre"

A ordem original deixa global CSS por último. **Argumentos contra:**

- O Painel do Mestre é a refatoração de mais alto risco (`index.php` reescrito + nova seção "última atividade crítica" + sparklines). Construído sobre a paleta antiga, ele vai precisar de ajustes finos quando os refinamentos chegarem (cores envenenadas, redução de glows, assimetrias).
- Fundação refinada primeiro = ajustes acontecem **uma vez só**. Caso contrário: refator do Painel → refator visual global → re-validação visual do Painel.

**Argumento a favor da ordem original:** refinamentos são polimento, fácil de aplicar no fim. Real, mas o trade-off é um round extra de QA visual no Painel.

**Recomendação:** mover "Background + refinamentos" para o passo 5 (entre cards de criatura e Painel do Mestre). Hero permanece por último — é cinematográfica e gateada por first-visit/F5; testar isolada no fim é natural.

### Hero antes ou depois do Painel?

**Depois.** Hero vive em `index.php` (mesmo arquivo do Painel, mas seção dedicada). Refatorando o Painel primeiro, validamos que o resto da home está estável; depois adicionamos a animação cinematográfica sem interferência. Inversamente, fazer Hero antes obrigaria a coexistir com a versão antiga do dashboard durante a transição. Hero por último = melhor sequência de validação.

---

## Ordem de execução final recomendada (7 passos)

| # | Passo | Componentes recebidos | Risco | Arquivos principais |
|---|---|---|---|---|
| 1 | Logo + Menu hambúrguer | 1, 8 | Baixo | `views/cabecalho.php`, `assets/css/terminal.css`, opcional `assets/img/logo-stacked.svg` |
| 2 | FA icons p/ elementos | 6 | Baixo | `views/cabecalho.php` (CDN no `<head>`), `criaturas/listar.php`, `criaturas/visualizar.php`, `assets/css/terminal.css` |
| 3 | Geo dice icons (só contorno) | 5 | Médio | `rolagem/index.php` (sprite), `assets/js/dados.js`, `assets/css/terminal.css` |
| 4 | Cards de criatura no bestiário | 4 | Baixo | `criaturas/listar.php`, `assets/css/terminal.css` |
| 5 | Background contínuo + refinamentos | 7, 9 | **Alto** | `assets/css/terminal.css` (global), opcional `assets/img/grain.svg` |
| 6 | Painel do Mestre (rebuild) | 3 | **Alto** | `index.php`, `assets/css/terminal.css`, possivelmente `src/LogRepositorio.php` |
| 7 | Hero D20 refinada | 2 | Médio | `assets/js/hero.js`, `assets/css/terminal.css`, `index.php` (markup hero) |

---

## Dependências entre componentes

```
Passo 1 (Logo + Hambúrguer)         ──┐
                                       │ ambos editam cabecalho.php
Passo 2 (FA icons)                  ──┤ adiciona <link> CDN no <head> de cabecalho.php
                                       │
                                      → fazer 1 e 2 em commits separados, mas adjacentes.

Passo 3 (Geo dice)                   independente — sprite vive em rolagem/index.php
                                     (verificado via grep). Sem impacto nos passos 1-2.
                                     Deps: dados.js NÃO pode mudar (IDs do sprite estáveis).

Passo 4 (Cards de criatura)          DEPENDE de Passo 2 — usa <i class="fa-solid">

Passo 5 (Background + refinamentos)  global; afeta tudo que veio antes,
                                     mas não quebra (paleta nominal, mesmas variáveis).

Passo 6 (Painel do Mestre)           DEPENDE de:
                                     - Passo 1 (logo no topbar)
                                     - Passo 2 (FA icons em sparklines/atalhos rápidos)
                                     - Passo 5 (paleta refinada como base do hero do dashboard)
                                     Possível dep do Passo 3 se "última rolagem" usar geo dice.

Passo 7 (Hero D20)                   DEPENDE de:
                                     - Passo 1 (logo aparece após o dado parar)
                                     - Passo 5 (paleta + glow reduzido aplicados)
```

---

## Risco por componente

| Passo | LOC esperado | Arquivos tocados | Cobertura | Risco |
|---|---|---|---|---|
| 1 | ~50–100 | 2 | Componente (header) | Baixo |
| 2 | ~30–60 | 4 | Componente (bestiário) | Baixo |
| 3 | ~80–150 | 3 | JS-acoplado (sprite) | Médio |
| 4 | ~50–80 | 2 | Componente (card) | Baixo |
| 5 | ~200–400 | 1–2 | **Global** | **Alto** |
| 6 | ~300–600 | 2–4 | Página principal + CSS + repos | **Alto** |
| 7 | ~80–150 | 3 | Componente + animação | Médio |

**Total estimado:** ~800–1500 LOC tocadas, em 7 passos validados individualmente.

---

## Checklist de integração

> Status: ⏳ pendente · 🔄 em andamento · ✅ concluído · ⚠️ bloqueado

### Passo 1 — Logo redesenhada + Menu hambúrguer com logo
- **Status:** ⏳ pendente
- **Fontes do design system:**
  - `_design_import/clarivid-ncia-paranormal-design-system/project/assets/logo-stacked.svg` (versão final aprovada — 480×320, com 3 elementos: "CLARIVIDÊNCIA" + "PARANORMAL" + icosaedro)
  - `_design_import/.../assets/logo-mark.svg` (apenas o icosaedro 48×48 — útil no menu hambúrguer)
  - `_design_import/.../assets/logo.svg` (variante horizontal — não usar; sem ícone do icosaedro)
  - `_design_import/.../preview/logo.html` (estratégia de implementação **HTML+CSS+SVG inline** — não usa o `.svg` como bloco único)
  - `_design_import/.../ui_kits/painel/Header.jsx` (referência do uso no menu hambúrguer — classes `.pn-logo__top/bot/mark`)
- **Estratégia de implementação:**
  - **Topbar (`.marca`)**: manter o markup atual (texto inline com `>` + `_`) — o design system **mantém o estilo terminal** no topbar conforme `Header.jsx`. Mudanças: `letter-spacing` no `.marca` para 0.06em, `marca__realce` (PARANORMAL) sem glow nominal (apenas cor `--el-conhecimento`).
  - **Menu hambúrguer (painel-nav)**: bloco novo `.painel-nav__logo` com classes `.pn-logo__top` (CLARIVIDÊNCIA pequeno, ls 0.62em), `.pn-logo__bot` (PARANORMAL grande, peso 900, glow dourado), `.pn-logo__mark` (SVG inline do icosaedro do `logo-mark.svg`).
  - **NÃO** usar `<object data="logo-stacked.svg">` no app — `Header.jsx` mostra que cada elemento é HTML+SVG separado (texto selecionável, fonte real do sistema, cor controlável via `currentColor`).
- **Arquivos do projeto a tocar:**
  - `views/cabecalho.php` (atualizar `.marca` no topbar + adicionar `.painel-nav__logo` no painel hambúrguer)
  - `assets/css/terminal.css` (regras `.marca*`, novas regras `.pn-logo*`, ajustes em `.painel-nav`)
  - **Opcional:** copiar `_design_import/.../assets/logo-stacked.svg` para `assets/img/logo-stacked.svg` se quisermos um asset de SPLASH/login (fora do app principal). Não imediatamente necessário.
- **Riscos:**
  - Letter-spacing 0.62em em `.pn-logo__top` aumenta width perceptível — testar 380px
  - Glow dourado (`text-shadow` 3 camadas) em `.pn-logo__bot` aumenta paint cost — desligar via `prefers-reduced-motion`
- **Validação:**
  - [ ] Topbar renderiza em 1024 / 768 / 540 / 380
  - [ ] Hambúrguer abre e mostra a logo stacked com o ícone do icosaedro abaixo
  - [ ] Sem horizontal scroll em nenhum breakpoint
  - [ ] Texto da logo permanece selecionável (estratégia HTML, não SVG-único)

### Passo 2 — Ícones de elementos (decisão SVG local — ver "Decisões")
- **Status:** ⏳ pendente
- **Fontes do design system:**
  - `_design_import/.../assets/elementos-icons.svg` (sprite com 5 `<symbol>`: `el-sangue`, `el-morte`, `el-conhecimento`, `el-energia`, `el-medo` — 24×24 viewBox, stroke-only com `currentColor`)
  - `_design_import/.../preview/cards_criatura.html` (referência visual — usa FA `fa-droplet/skull/eye/bolt/hurricane`; vamos REIMPLEMENTAR com SVG local)
- **Glifos do sprite local (mantendo a semântica dos protótipos):**
  - `#el-sangue` — gota (mesmo conceito que `fa-droplet`)
  - `#el-morte` — caveira simplificada (mesmo conceito que `fa-skull`)
  - `#el-conhecimento` — triângulo + olho dentro (mesmo conceito que `fa-eye`)
  - `#el-energia` — raio (mesmo conceito que `fa-bolt`)
  - `#el-medo` — espirais concêntricas / vórtice (paralelo a `fa-hurricane`)
- **Estratégia de implementação:**
  - Copiar `elementos-icons.svg` para `assets/img/elementos-icons.svg` (fica como asset versionado).
  - Incluir o sprite uma única vez via `<?php include 'caminho-do-sprite.svg'; ?>` em `views/cabecalho.php` (ou inline no `<body>` topo).
  - Usar `<svg class="el el--sangue"><use href="#el-sangue"/></svg>` em criaturas/listar.php e criaturas/visualizar.php.
  - CSS aplica cor + drop-shadow por elemento (`color: var(--el-sangue); filter: drop-shadow(0 0 6px var(--el-sangue))`) — o `currentColor` no `<symbol>` herda automaticamente.
- **Arquivos do projeto a tocar:**
  - `assets/img/elementos-icons.svg` (NOVO — copiado do design system)
  - `views/cabecalho.php` (incluir sprite uma vez por página)
  - `criaturas/listar.php` (substituir tag textual por `<svg><use>`)
  - `criaturas/visualizar.php` (mesmo)
  - `assets/css/terminal.css` (regras `.el`, `.el--sangue` ... `.el--medo`)
- **Acessibilidade:**
  - Adicionar `<title>` dentro de cada `<symbol>` (ex: `<title>Sangue</title>`)
  - Usar `role="img"` + `aria-labelledby="..."` no `<svg>` consumidor, ou `aria-label="Sangue"` direto
- **Riscos:**
  - Forma do `el-medo` (espirais concêntricas) é a mais "estilizada" — validar legibilidade em 16/19px
  - `currentColor` herda do `color:` do pai — garantir que CSS `.el--sangue { color: var(--el-sangue) }` está aplicado antes do `<svg>` herdar
- **Validação:**
  - [ ] 5 ícones renderizam em listagem e dossiê de criaturas
  - [ ] `currentColor` aplica a cor canônica de cada elemento
  - [ ] Screen reader anuncia o nome do elemento via `<title>` ou `aria-label`
  - [ ] Sprite é incluído uma única vez por página (não duplicado)

### Passo 3 — Ícones geométricos dos dados (só contorno externo)
- **Status:** ⏳ pendente
- **Fontes do design system:**
  - `_design_import/.../assets/dice-icons.svg` (sprite com `<symbol id="d4|d6|d8|d10|d12|d20|d100|sigil">`, viewBox 64×64, stroke-only, `currentColor`)
  - `_design_import/.../preview/dice_icons.html` (referência de hover/active states)
- **⚠️ Conflito de IDs detectado:**
  - **Nosso sprite** (em `rolagem/index.php` linhas 72-108): IDs são `geo-d4`, `geo-d6`, ... `geo-d100`
  - **Sprite deles** (em `dice-icons.svg`): IDs são `d4`, `d6`, ... `d100` (sem prefixo)
  - **Nossa referência** (`rolagem/index.php:125`): `<use href="#geo-<?= $tipo ?>"/>`
  - **Decisão 008 do `decisoes.md`** documenta nosso prefixo `geo-` como canônico
- **Estratégia de resolução:**
  - **Renomear os IDs do sprite deles para `geo-d4..geo-d100` ao copiar** (mantém compat com `dados.js` e respeita ADR 008). Sed simples no copy.
  - Sprite vai para `assets/img/dice-icons.svg` e o `rolagem/index.php` passa a referenciar via `<use href="assets/img/dice-icons.svg#geo-dN"/>` ou via inclusão como inline.
  - Migrar para inclusão global em `views/cabecalho.php` se Painel do Mestre (passo 6) for usar (provável — "última rolagem" deve mostrar o tipo).
- **Diferenças visuais já adquiridas no sprite deles:**
  - Apenas contorno externo (sem linhas internas como D20 atual tem) ✅ atende ao componente 5 do brief
  - Texto centralizado "D4", "D6", "D20"... em JetBrains Mono dentro do SVG ✅
- **Arquivos do projeto a tocar:**
  - `assets/img/dice-icons.svg` (NOVO — copiado E renomeado de `dN` para `geo-dN`)
  - `views/cabecalho.php` (mover sprite pra cá se Passo 6 usar; senão deixar em `rolagem/index.php`)
  - `rolagem/index.php` (substituir o `<svg>` inline pelos novos `<symbol>`)
  - `assets/js/dados.js` (**NÃO** mudar — depende dos IDs `geo-dN` que vamos preservar)
  - `assets/css/terminal.css` (estilos em `.dado-svg__forma`, hover/active)
- **Riscos:**
  - **Decisão 011** (tripla defesa `hidden`/`display="none"`/`.is-oculta`) — verificar que o sprite copiado preserva o atributo `display:none` no `<svg>` raiz para esconder os símbolos
  - Se esquecer o rename `dN → geo-dN`, todos os ícones dos dados desaparecem da página de rolagem
- **Validação:**
  - [ ] Cada tipo de dado renderiza só o contorno externo
  - [ ] `dados.js` alterna entre formas sem warnings (IDs `geo-dN` preservados)
  - [ ] Decisão 011 ainda funciona (testar Edge + Chrome)
  - [ ] Sprite não duplica em DOM se incluído via cabecalho.php

### Passo 4 — Cards de criatura no bestiário
- **Status:** ⏳ pendente
- **Arquivos:**
  - `criaturas/listar.php` (markup do `.cartao-criatura`)
  - `assets/css/terminal.css` (todas as classes `.cartao-criatura*`)
- **Riscos:**
  - Overflow do "CONHECIMENTO" sugere que o card atual não acomoda labels longos. Confirmar se a solução é encurtar o texto, redimensionar o container ou usar tipografia condensada.
  - Se o card mudar de altura, a grade reflowa — testar com 1 / 5 / 20 criaturas.
- **Validação:**
  - [ ] "CONHECIMENTO" não vaza em 380px width
  - [ ] Cards de altura uniforme (CSS Grid `auto-fit`)
  - [ ] Tag de elemento usa FA icon do Passo 2
- **Notas:** boa oportunidade para extrair tokens de elemento se ainda não estão em `:root`.

### Passo 5 — Background contínuo + refinamentos estéticos globais
- **Status:** ⏳ pendente
- **Fontes do design system:**
  - `_design_import/.../colors_and_type.css` (135 linhas — extração dos nossos tokens **+ tokens novos** que ainda não estão em `:root`)
  - `_design_import/.../ui_kits/painel/index.html` (linhas 7-39 — paleta envenenada page-scoped via classe `.app` + grain SVG inline + radial gradients contínuos)
  - `_design_import/.../assets/terminal.css` (97 KB — **diff -q confirma: IDÊNTICO ao nosso** `assets/css/terminal.css`. Não há código novo aqui; é a snapshot do nosso CSS)
- **Descoberta crítica — :root nosso vs deles:**
  - Variáveis **JÁ presentes em ambos** (canvas, surface-1/2/3, metalico, border-*, ink-*, el-*, glow-*, critico/falha/aviso, fontes, --r, --gutter): **idênticas**
  - Variáveis **novas no `colors_and_type.css`** (a adicionar no nosso `:root`):
    - **Spacing:** `--space-1..8` (4/8/12/16/20/24/32px)
    - **Type semantic:** `--t-h1, --t-h2, --t-h3, --t-display, --t-ui, --t-label, --t-body, --t-small, --t-mono` (shorthands `font:`)
    - **Letter-spacing:** `--ls-tight, --ls-base, --ls-ui, --ls-label, --ls-caps, --ls-wide`
    - **Shadow:** `--shadow-card, --shadow-lift, --shadow-inset`
    - **Animation:** `--ease-standard, --ease-overshoot, --t-fast, --t-base, --t-slow`
- **🔴 Nota crítica — fonte da paleta envenenada:**
  - O `_design_import/.../assets/terminal.css` recebido é **idêntico ao nosso atual** (`diff -q` confirmou). **Não há paleta envenenada lá** — só a versão atual neon.
  - A paleta envenenada vive **APENAS** em `_design_import/.../ui_kits/painel/index.html` (linhas 9-15), dentro de um bloco `.app { ... }`. **Extrair os hex codes de lá**, não esperar encontrá-los no `colors_and_type.css` ou no `terminal.css` deles.
- **Estratégia de implementação (refinada via Decisão D4):**
  - **Reforma direta no `:root`** do `terminal.css` — não usar bloco `.app` scoped. Paleta envenenada vira a paleta canônica.
  - Mapeamento dos hex codes envenenados (extrair de `index.html` do painel) → vars do nosso `:root`:
    - `--el-conhecimento`: `#ffd60a` → `#e0b53d` (mostarda envelhecida, vs neon)
    - `--el-energia`: `#9d4edd` → `#7b4d9e` (roxo cinza, dessaturado)
    - `--el-sangue`: `#c8102e` → `#a53846` (sangue antigo)
    - `--critico`: `#00ff66` → `#5fa873` (verde fosforescente apagado)
    - `--el-morte`: avaliar — design system não muda explicitamente
    - `--el-medo`: avaliar — design system não muda explicitamente
    - **NEW:** `--gold-dim: #a08527` (helper para variantes de dourado)
    - **NEW:** `--ink-paper: #d8c9a5` (helper para texto sobre fundo dourado)
  - **Adicionar tokens novos do `colors_and_type.css`** ao `:root` (Decisão D3): `--space-1..8`, `--t-h1..mono`, `--ls-tight..wide`, `--shadow-card/lift/inset`, `--ease-standard/overshoot`, `--t-fast/base/slow`. Conflito zero, puro acréscimo.
  - **Background contínuo** = body com 5+ radial gradients fixos (não recortados por seção) + `body::before` com SVG noise inline base64 (1px grain @ 5.5% opacity) + `body::after` com vignette radial. Extrair markup do `index.html` do painel (linhas 17-39).
  - **"Glow REMOVED"** marcadores no `index.html` (procurar por essa string literal) indicam onde reduzir glows. Auditar `.marca__realce`, `.hero__h1`, etc.
- **Tokens da paleta envenenada (escopo `.app`):**
  - `--gold: #e0b53d` (mostarda envelhecida, vs neon `#ffd60a`)
  - `--gold-dim: #a08527`
  - `--purple: #7b4d9e` (roxo cinza, dessaturado vs `#9d4edd`)
  - `--red: #a53846` (sangue antigo vs `#c8102e`)
  - `--green: #5fa873` (fosforescente)
  - `--paper: #d8c9a5`
- **Arquivos do projeto a tocar:**
  - `assets/css/terminal.css` (acrescentar tokens novos em `:root`; adicionar bloco `.app { ... }` com paleta envenenada; novo `body::before` para grain SVG; ajustar `body` background com gradients contínuos; auditar glows decorativos)
  - **Sem novo arquivo:** grain SVG inline base64 (já feito no protótipo); não precisamos de arquivo separado
- **Riscos:**
  - **GLOBAL:** qualquer mudança em `:root` afeta tudo. Plano de teste cobrir 11+ páginas: index, campanhas (3 fluxos), agentes (3), npcs (3), criaturas (3), rolagem, histórico
  - **Reforma direta:** dessaturação da paleta atinge instantâneamente todos os componentes que usam `--el-X`. Glows, borders, hovers, criatura cards — tudo muda na hora. **Fazer commit isolado** que pode ser revertido se a auditoria visual quebrar
  - Contraste das cores envenenadas: `#e0b53d` em texto pequeno sobre `#0a0a0a` precisa de auditoria AA — `#e0b53d` deve estar OK (relação ~9:1) mas validar
  - Grain SVG inline em `data:` URI base64 aumenta tamanho do CSS — aceitável (~2 KB)
- **Validação:**
  - [ ] 11+ páginas renderizam sem regressão visual perceptível além do ajuste de paleta
  - [ ] Contraste mínimo AA preservado (texto sobre canvas ≥ 4.5:1)
  - [ ] Grain não introduz banding em telas Retina
  - [ ] Background não se "corta" entre seções (gradientes fixed cobrem scroll inteiro)

### Passo 6 — Painel do Mestre (rebuild da home)
- **Status:** ⏳ pendente
- **Fontes do design system:**
  - `_design_import/.../ui_kits/painel/index.html` (48 KB — protótipo HTML+CSS completo da home reformulada)
  - `_design_import/.../ui_kits/painel/Header.jsx`, `Components.jsx` (referência de markup React; reescrever em PHP)
- **Arquivos:**
  - `index.php` (refator pesado da página)
  - `views/cabecalho.php` (adicionar **Cormorant Garamond** + **IM Fell English** ao `<link>` do Google Fonts — Decisão D5)
  - `assets/css/terminal.css` (novas seções: `.painel-hero`, `.faixa-stats`, `.trilhas-investigacao`, `.sussurros`, `.atalhos-rapidos`; vars `--font-sussurro`, `--font-poetica` em `:root`)
  - **Possivelmente:** `src/LogRepositorio.php` (novo método para "última atividade crítica" — ex: última rolagem com `eh_critico=1` ou `eh_desastre=1`)
  - **Possivelmente:** `src/CampanhaRepositorio.php` (dados para sparklines — ex: rolagens por dia nos últimos 7d)
- **Riscos:**
  - **ALTO:** sparklines exigem dados agregados. Se o backend não tem ainda, precisamos criar SQL + método de repositório. Queries por request podem ficar caras com 1000+ rolagens; avaliar cache na sessão ou agregação por dia.
  - Card "última atividade crítica" — definir critério: apenas `eh_critico=1`/`eh_desastre=1`, ou também eventos do sistema?
  - "Sussurros do Outro Lado" — fonte dos textos? Pool fixo aleatório no PHP? Ou tabela nova?
  - Hero do dashboard ≠ Hero d20 do passo 7. Não confundir nomenclatura no CSS.
- **Validação:**
  - [ ] 5 seções renderizam em ordem cinematográfica
  - [ ] "Última atividade crítica" mostra a rolagem real mais recente com flag
  - [ ] Sparklines têm dados verídicos (não placeholder)
  - [ ] Performance: dashboard carrega em < 200ms com 1000+ rolagens
  - [ ] Layout responsivo nos 4 breakpoints
- **Notas:** considerar lazy-render dos sparklines (Intersection Observer) se houver impacto de paint.

### Passo 7 — Hero do D20 refinada
- **Status:** ⏳ pendente
- **Arquivos:**
  - `assets/js/hero.js` (timing, easing, sequência logo-aparece-após)
  - `assets/css/terminal.css` (seção `.hero-rolar-3d`, keyframes)
  - `index.php` (markup do hero, se mudar)
- **Riscos:**
  - Animação de 4–5s com queda realista — `cubic-bezier()` complexo precisa ser validado em Chrome / Edge / Firefox.
  - **Decisão 019** (Hero apenas em first-visit/F5) deve ser preservada — `deveRodarHero()` é gate crítico via Performance Navigation API.
  - Áudio sincronizado (`som_para_a_hero.mp3`, ~3.5s) — se animação for para 5s, áudio fica curto. Avaliar repetir, esticar (não recomendado), ou re-encodar (item do backlog: normalização ffmpeg).
  - "Brilho dourado em 20" — confirmar se conflita com critério "20 verde / 1 vermelho" das rolagens normais (Decisão 007). Hero pode ter regra própria.
- **Validação:**
  - [ ] Animação roda em first-visit (sessionStorage vazio)
  - [ ] F5 re-dispara
  - [ ] Cliques internos NÃO disparam (Decisão 019)
  - [ ] Logo aparece sincronizada com final do tilt do dado
  - [ ] Mobile (380px) renderiza fluido (≥ 50fps)
- **Notas:** se áudio dessincronizar, item ffmpeg pode subir do backlog para esta sessão.

---

## Decisões durante a integração

> Registrar aqui escolhas não-óbvias feitas durante a integração.
> Ao final, migrar cada uma para `decisoes.md` como ADR 023+.

### D1 (2026-05-08) — Ícones de elementos: SVG local em vez de FontAwesome ✅ APROVADO
- **Contexto:** O design system extraído trouxe **dois caminhos**: (a) `_design_import/.../assets/elementos-icons.svg` (sprite local com 5 `<symbol>`) e (b) protótipos HTML em `preview/cards_criatura.html` e `ui_kits/painel/index.html` que usam FontAwesome 6 via CDN (`fa-droplet`, `fa-skull`, `fa-eye`, `fa-bolt`, `fa-hurricane`).
- **Decisão:** Usar o **SVG local** (`elementos-icons.svg`) — copiar para `assets/img/elementos-icons.svg` e referenciar via `<svg><use href="#el-X"/></svg>`.
- **Nota:** Os HTMLs de preview que usam FA são **inconsistentes com a própria documentação** do design system (`project/README.md` declara literalmente "There is no Font Awesome"). Devem ser ignorados como referência para a tag de elemento; só servem para o layout estrutural do card.
- **Trade-off — prós SVG local:**
  - Zero dep de runtime (alinhado com `claude.md §2`, "zero dependências de runtime")
  - 2 KB inline vs ~30 KB do FA via CDN
  - `currentColor` integra perfeitamente com nossas vars CSS de elemento (`--el-sangue`, etc.)
  - Consistente com o pattern já estabelecido (`rolagem/index.php` usa SVG sprite para dados)
  - Permite `<title>` semântico dentro de cada `<symbol>` para a11y
  - Sem FOIT/FOUT da CDN
- **Trade-off — contras:**
  - Drift visual potencial vs protótipo do design system (que usa FA) — mitigado por: o design system **mesmo** PROVEU `elementos-icons.svg` como alternativa local, indicando que ambos os caminhos são "blessed"
  - Manutenção manual de novos ícones (não é problema imediato — só temos 5 ícones de elemento)
- **Por que NÃO FA:**
  - Adicionar CDN externo seria precedente perigoso vs disciplina zero-deps
  - Estilo "padrão" do FA (preenchido, glifos uniformes) destoa da linguagem do projeto (stroke-only, gold accent, sharp angles) que já está em logo-mark.svg, dice-icons.svg, d20-hero.svg
  - 5 ícones não justificam 30 KB de CSS+font da CDN
- **Status:** Decisão tomada — aguardando aprovação do usuário; vai virar ADR 023 em `decisoes.md` após implementação do Passo 2.

### D2 (2026-05-08) — IDs do sprite de dados: manter prefixo `geo-`
- **Contexto:** O sprite `dice-icons.svg` do design system usa IDs `d4..d100` (sem prefixo). Nosso sprite atual em `rolagem/index.php` e nossa Decisão 008 (`decisoes.md`) canonizaram IDs `geo-d4..geo-d100`. `dados.js` referencia `<use href="#geo-dN">`.
- **Decisão:** Ao copiar `dice-icons.svg` para `assets/img/dice-icons.svg`, **renomear os IDs** de `dN` para `geo-dN` (sed simples). Mantém compat com `dados.js` sem alterá-lo e respeita ADR 008.
- **Status:** Decisão tomada — implementação no Passo 3.

### D3 (2026-05-08) — `colors_and_type.css` não substitui `:root`, apenas estende
- **Contexto:** Comparação `:root` deles vs nosso mostra que **todas as variáveis presentes em ambos são idênticas** (mesmas cores, mesmos gradients, mesmas fontes). O `colors_and_type.css` **adiciona** novos tokens (spacing scale, type semantic shorthands, letter-spacing tokens, shadow tokens, animation tokens) que ainda não existem no nosso `:root`.
- **Decisão:** No Passo 5, **estender** o `:root` atual do `terminal.css` com os tokens novos. Não substituir nada existente. Conflito zero.
- **Status:** Decisão tomada — implementação no Passo 5.

### D4 (2026-05-08) — Refinamentos estéticos: reforma DIRETA no `:root` global ✅ APROVADO (escolha global)
- **Contexto:** A "paleta envenenada" (mostarda `#e0b53d`, roxo cinza `#7b4d9e`, sangue antigo `#a53846`, etc.) aparece no `ui_kits/painel/index.html` como override page-scoped dentro de `.app { --gold: #e0b53d; ... }`. Considerada a alternativa de manter `.app` scoped vs reforma global em `:root`.
- **Decisão:** Reformar **diretamente as variáveis em `:root`** (`--el-sangue`, `--el-conhecimento`, `--el-energia`, etc) — paleta envenenada vira a paleta canônica do projeto.
- **Razões para preferir global sobre `.app` scoped:**
  - O projeto tem **uma única "app"** — scoping é proteção desnecessária
  - Refit gradual (manter as duas paletas em paralelo) cria ambiguidade visual durante a transição
  - O Passo 5 está agendado **antes** do Passo 6 (Painel do Mestre), então a reforma estará completa antes dos componentes serem refatorados
- **Estratégia técnica:**
  - Editar diretamente `--el-sangue`, `--el-conhecimento`, `--el-energia`, `--el-morte` no `:root` do `assets/css/terminal.css`
  - Avaliar `--ink` e `--ink-dim` se houver ajuste sugerido no design system
  - Pegar valores envenenados a partir de `_design_import/.../ui_kits/painel/index.html` (linhas 9-15: `--gold:#e0b53d`, `--gold-dim:#a08527`, `--purple:#7b4d9e`, `--red:#a53846`, `--green:#5fa873`, `--paper:#d8c9a5`)
  - Mapeamento: `--gold` → `--el-conhecimento`; `--purple` → `--el-energia`; `--red` → `--el-sangue`; `--green` → `--critico`; `--paper` → possível nova var `--ink-paper` (helper para texto sobre dourado)
  - Validar todas as 11+ páginas listadas no Passo 5 após a mudança
  - Auditoria de contraste WCAG AA via DevTools (texto sobre `--canvas` ≥ 4.5:1)
- **Status:** Decisão tomada — implementação direta no Passo 5.

### D5 (2026-05-08) — Fontes adicionais (Cormorant Garamond + IM Fell English) com escopo restrito ✅ APROVADO
- **Contexto:** O `ui_kits/painel/index.html` carrega 2 fontes adicionais via Google Fonts: `Cormorant Garamond` (italic, usada em `.hero__sub` para citações ❝...❞) e `IM Fell English` (mencionada para subtítulos poéticos diegéticos). Não constam nos 9 componentes do brief original.
- **Decisão:** Adicionar ambas com **escopo de uso restrito**:
  - **Cormorant Garamond** → usada APENAS nos textos do bloco "Sussurros do Outro Lado" (Painel do Mestre, seção atmosférica)
  - **IM Fell English** → usada APENAS em subtítulos poéticos diegéticos (ex: "Os véus estão tênues...")
  - Carregar via Google Fonts (mesma estratégia das outras 4 fontes — Decisão 004)
  - **NÃO** promover para `--corpo` ou `--titulo` em variáveis globais; criar variáveis específicas como `--font-sussurro` e `--font-poetica` se necessário
  - Adicionar ao `<link>` do Google Fonts em `views/cabecalho.php` **apenas no Passo 6** (quando o Painel do Mestre for refatorado), **não antes**
- **Razão:** Manter o sistema tipográfico de 4 famílias (Cinzel/Montserrat/Helvetica/JetBrains Mono) limpo para os componentes gerais; usar essas duas extras como "tinta de cena" só onde a atmosfera narrativa pede.
- **Status:** Decisão tomada — implementação no Passo 6, após Passo 5 estabelecer a paleta envenenada.

---

## Lições aprendidas

> Surpresas, armadilhas, atalhos descobertos.
> Útil para próxima refatoração visual grande.

_(vazio — preencher ao concluir)_

---

## Histórico de status

| Data | Evento | Nota |
|---|---|---|
| 2026-05-08 | Plano criado | aguardando handoff do Claude Design para `/_design_import/` |
| 2026-05-08 | Tentativa de handoff via URL `api.anthropic.com/v1/design/h/4CyG9epk7rsmEPg_tcdvjw` | **HTTP 404 NotFound** sem `WWW-Authenticate` (não é falha de auth — endpoint não responde para esse ID). Aguardando URL nova ou ZIP extraído manualmente em `/_design_import/`. |
| 2026-05-08 | Arquivos do design system extraídos via ZIP em `_design_import/clarivid-ncia-paranormal-design-system/` | 32 arquivos. Estrutura: `README.md`, `project/{README.md, SKILL.md, colors_and_type.css, assets/, components/, preview/, ui_kits/painel/}`. Mapeamento crítico realizado, decisões D1-D4 registradas, passos 1/2/3/5 do checklist atualizados com fontes específicas. **Descoberta:** `terminal.css` deles é **idêntico ao nosso** (`diff -q` confirma) — o "design system" é uma curadoria documentada do nosso CSS atual, não uma reforma. O que é genuinamente novo: 6 SVGs (3 logos, d20-hero estático, sprite dice, sprite elementos), tokens novos em `colors_and_type.css` (spacing/type/animation), paleta envenenada page-scoped no `index.html` do painel, hero standalone em `components/hero_d20.html`. |
