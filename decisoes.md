# Registro de Decisões

**Data:** 08 de maio de 2026
**Objetivo:** Documentar decisões cruciais de design, arquitetura e escolhas tecnológicas do projeto.

---

## Decisão 001: Padrão de Estrutura de Documentação
- **Contexto**: A equipe precisava de um formato padronizado para armazenar, consultar e preservar o contexto de decisões importantes para evitar debates repetidos [Documentar decisões como um processo central da equipe](https://medium.com).
- **Decisão**: Adoção do padrão ADR (*Architecture Decision Record*) para documentar decisões e formato Markdown para os relatórios.
- **Status**: Aprovado e em implementação.
- **Consequências**: Maior clareza histórica e facilidade de integração com repositórios Git [Basic writing and formatting syntax - GitHub Docs](https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax).

---

## Decisão 002: Rastreabilidade de Logs de Aplicação
- **Contexto**: Necessidade de auditoria, monitoramento e capacidade de rastrear a cronologia dos eventos que ocorrem no sistema [O que são arquivos de log? - AWS](https://aws.amazon.com/pt/what-is/log-files/).
- **Decisão**: Criação contínua e versionamento do arquivo `logs.md` para consolidar anualmente ou por sessão as atividades críticas.
- **Status**: Aprovado.
- **Consequências**: Facilidade no diagnóstico e na solução de problemas [Obtendo registros de eventos do Windows para diagnóstico e ...](https://www.autodesk.com/br/support/technical/article/caas/sfdcarticles/sfdcarticles/PTB/Obtaining-Windows-Event-logs-for-diagnostics-and-troubleshooting.html).

---

## Decisão 003: Helper `url()` para portabilidade de instalação
- **Contexto**: Os caminhos do projeto eram absolutos (`/npcs/listar.php`), o que quebra quando o site é servido a partir de uma subpasta como `http://localhost/clarividenciarpg/`.
- **Decisão**: Criar a função `url($caminho)` em `config.php` que detecta automaticamente o sub-diretório comparando `realpath(__DIR__)` com `$_SERVER['DOCUMENT_ROOT']`. Todos os links HTML, `header('Location:')`, `form action`, `script src` e `link href` usam esse helper.
- **Status**: Aprovado e implementado.
- **Consequências**: Funciona em XAMPP (`/clarividenciarpg/`), Virtual Host (raiz) ou produção sem alteração de código. JavaScript de rolagem lê a URL da API via `data-api` no form, evitando hardcode no JS.

---

## Decisão 004: Tipografia em três camadas (Cinzel / Montserrat / Helvetica)
- **Contexto**: O sistema usava apenas JetBrains Mono. O brief pediu Clarkson (fonte paga proprietária) para títulos, Montserrat para UI e Helvetica para corpo.
- **Decisão**:
  - Substituir Clarkson por **Cinzel** (Google Fonts) — serifada robusta com vibe ritualística que casa melhor com o tema Ordem Paranormal do que tipos puramente "terminal".
  - **Montserrat** para botões, labels, navegação e elementos de interface.
  - **Helvetica** (com fallback `Arial, sans-serif`) para corpo de texto.
  - **JetBrains Mono** preservada em códigos, prompts (`>`), IDs e timestamps — onde mono é semântico.
- **Status**: Aprovado e implementado.
- **Consequências**: Variáveis CSS `--titulo`, `--ui`, `--corpo`, `--mono` permitem trocar Clarkson real depois sem refactor.

---

## Decisão 005: Menu hambúrguer pure-CSS sempre visível
- **Contexto**: O brief pediu hambúrguer responsivo com checkbox invisível (sem JS) e glassmorphism.
- **Decisão**: Ícone hambúrguer visível em **todas** as resoluções (não só mobile). Painel desliza da direita com `backdrop-filter: blur(...)`. A barra de navegação tradicional foi substituída pelo painel.
- **Status**: Aprovado e implementado.
- **Consequências**: Mais imersivo ("menu secreto que se revela") e remove o conflito de ter dois sistemas de navegação. Acessibilidade mantida via `aria-*` e suporte a teclado.

---

## Decisão 006: Hero/Splash — exibir uma vez por sessão
- **Contexto**: Brief pede tela de boas-vindas com d20 rolando e parando no 20. Mostrar 3+ segundos de animação a cada navegação seria irritante.
- **Decisão**: Hero exibida **uma vez por sessão** controlada por `sessionStorage.terminalHeroVisto`. JS mínimo (~10 linhas) só gerencia a flag — a animação é 100% CSS keyframes.
- **Status**: Aprovado e implementado.
- **Consequências**: Primeira impressão imersiva sem fricção em uso contínuo. Pode ser forçada novamente em aba anônima.

---

## Decisão 007: Sistema multi-dado preserva regra Ordem Paranormal
- **Contexto**: Brief pede suporte a d4, d6, d8, d10, d12, d20, d100. O sistema atual implementa a regra canônica de Ordem Paranormal (rola N d20, pega o maior; se atributo=0 rola 2 e pega menor).
- **Decisão**:
  - Quando o tipo for **d20**, a regra de Ordem Paranormal continua valendo (com o seletor de atributo).
  - Para **d4, d6, d8, d10, d12, d100**, o sistema rola 1 dado simples (regra de vantagem não se aplica). O campo "atributo" é ocultado nesses casos.
  - Brilho intenso **apenas em 1 e 20** (conforme brief), independente do tipo de dado rolado.
- **Status**: Aprovado e implementado.
- **Consequências**: Migration `sql/migration_002_tipo_dado.sql` adiciona coluna `tipo_dado ENUM(...)` à `log_rolagens` e relaxa o CHECK do `resultado_final` para 1..100. Histórico passa a exibir o tipo.

---

## Decisão 008: Reforma VTT entregue em 5 fases
- **Contexto**: O brief "Reforma Estrutural e Estética" propôs transformar o sistema de painel de mestre em VTT completo: novo schema (campanhas, agentes, perícias, ataques, inventário, rituais), refresh visual com obsidiana/metálico, ícones geométricos para os dados, Hero 2.0 com profundidade 3D, ficha de agente massiva (~30 campos + listas dinâmicas), sistema de combate calculado, upload de imagens. Tudo num único turno produziria código superficial e inseguro.
- **Decisão**: Entregar em fases iterativas, validando cada fase com o usuário antes da próxima:
  1. **Fundações** (DB, refresh visual, ícones geométricos, estrutura de uploads protegida) — *este turno*.
  2. **Hero 2.0 + CRUD de Campanhas** com upload de capa.
  3. **Layout estático** da ficha de agente (sem CRUD).
  4. **CRUD do Agente** + barras dinâmicas de PV/SAN/PE em JS.
  5. **Sistema de Combate** (ataques calculados), perícias, inventário/rituais dinâmicos.
- **Status**: Fase 1 implementada.
- **Consequências**:
  - Migration `sql/migration_003_vtt.sql` cria 6 tabelas novas + ALTER em `npcs`/`criaturas` para suportar `campanha_id` e `foto_arquivo`.
  - Pasta `/uploads/` com `.htaccess` que **bloqueia execução PHP/CGI** — defesa contra upload-RCE clássico. Deny `Require all denied` em extensões executáveis + `Options -Indexes`.
  - Variáveis CSS `--metalico*`, `--glow-*` separam o "papel" do canvas escuro do destaque colorido. Cores dos elementos (Sangue/Morte/Conhecimento/Energia) vão para o glow, não para fills permanentes.
  - Ícones geométricos dos dados são **SVG sprites com `<symbol>`** (não bibliotecas externas), reaproveitáveis em qualquer página via `<use href="#geo-dN">`.

---

## Decisão 009: Substituir Clarkson por Cinzel definitivamente
- **Contexto**: Clarkson é fonte paga e o brief a citou como sugestão ("ou similar de terminal/serifada robusta"). Ao testar Cinzel no contexto Ordem Paranormal, a leitura ficou superior à intenção original.
- **Decisão**: Manter Cinzel como `--titulo` e remover do roadmap a "troca futura para Clarkson real". Se algum dia surgir orçamento para Clarkson, basta trocar a variável.
- **Status**: Confirmado pelo uso.
- **Consequências**: Sem custo de licenciamento. Carregamento via Google Fonts já está configurado.

---

## Decisão 010: Adicionar Medo como 5º elemento da paleta (background sutil)
- **Contexto**: O brief de "Reforma Estrutural" pediu para basear a paleta nos elementos canônicos de Ordem Paranormal. Os 4 elementos clássicos (Sangue, Morte, Conhecimento, Energia) já estavam mapeados, mas faltava o "elemento mestre" Medo — referido na lore como o vazio primordial do Outro Lado.
- **Decisão**: Adicionar `--el-medo: #5a1d8a` (púrpura escuro) e seu glow correspondente. **Não** usar como cor de fill ou border permanente — apenas como gradiente radial sutil no `body` (canto superior esquerdo) e na Hero (centro), invocando atmosfera. Rationale: Medo não é um "elemento que se aprende", é o pano de fundo cosmológico.
- **Status**: Implementado.
- **Consequências**: Background do site deixou de ser preto puro e ganhou textura sem sacrificar legibilidade. Variável também usa-se na 2ª onda de choque da Hero (combinação Conhecimento → Sangue → flash).

---

## Decisão 011: Bug do `<g hidden>` em SVG resolvido com tripla defesa
- **Contexto**: O atributo HTML5 `hidden` deveria aplicar `display: none` em qualquer elemento. Entretanto, em alguns navegadores (e contextos de inline SVG dentro de `<details>` colapsáveis), o atributo é ignorado em elementos `<g>`, causando renderização sobreposta de múltiplas formas de dado simultaneamente.
- **Decisão**: Triplicar a sinalização de "oculto" para garantir que pelo menos um caminho funcione em todos os ambientes:
  1. Atributo HTML `hidden` (semântico — primeira linha de defesa).
  2. Atributo SVG nativo `display="none"` (presentation attribute — segunda linha).
  3. Classe CSS `.is-oculta` com `!important` aplicada via JS (terceira linha).
  Adicionalmente, regra CSS combinada `[hidden], [display="none"], .is-oculta { display: none !important }` em `.dado-svg__forma`.
- **Status**: Implementado e validado em Chrome/Edge no XAMPP local.
- **Consequências**: Robustez contra implementações inconsistentes de SVG. Custo: 3x trabalho no `dados.js` ao alternar formas; impacto de performance desprezível.

---

## Decisão 012: Layout da ficha de Agente em página única (não tabs)
- **Contexto**: A ficha tem 9 seções (Identidade, Barras, Atributos, Defesa, Narrativa, Perícias, Ataques, Inventário, Rituais) com cerca de 30 campos diretos + 4 listas dinâmicas. Tabs (abas horizontais) seriam mais compactas, mas exigem JS para navegação e quebram impressão.
- **Decisão**: Página única longa com cada seção em `<details>`/`<summary>` HTML5 nativo. Cada seção pode ser aberta ou recolhida individualmente. Carregamento inicial: Identidade, Barras e Atributos abertas; demais recolhidas.
- **Status**: Implementado.
- **Consequências**:
  - Zero JS para navegação entre seções.
  - Impressão funciona automaticamente (todas as seções abertas no print).
  - Form único submete tudo de uma vez (transação ACID no banco).
  - Trade-off: scroll mais longo em telas pequenas. Aceito porque a ficha é referência completa, não consulta rápida.

---

## Decisão 013: Salvamento transacional da ficha (DELETE + INSERT para filhas)
- **Contexto**: A ficha de Agente tem 4 tabelas filhas (perícias, ataques, inventário, rituais) com cardinalidade 1:N. Cada save pode adicionar, remover ou modificar dezenas de linhas. Estratégias possíveis: (a) diff/upsert por ID, (b) DELETE total + INSERT.
- **Decisão**: DELETE + INSERT dentro de uma `BEGIN TRANSACTION` única que cobre o `UPDATE` em `agentes` e os 4 ciclos delete-insert. ROLLBACK em qualquer erro.
- **Status**: Implementado em `AgenteRepositorio::criar()` e `atualizar()`.
- **Consequências**:
  - Implementação trivial — não precisa rastrear IDs no front-end.
  - Atômico: ficha nunca fica em estado parcial.
  - IDs das filhas mudam a cada save. Aceitável porque nada referencia esses IDs externamente.
  - Volume comportável: uma ficha tem dezenas de itens, não milhares. Performance é não-questão.

---

## Decisão 014: Publicação no GitHub preservando histórico do remoto
- **Contexto**: O repo remoto `arturmuller25/clarividenciarpg` tinha 1 commit inicial (importação manual). A pasta de trabalho local foi extraída de um ZIP e modificada extensivamente — sem histórico git. Como publicar mudanças sem perder o commit inicial?
- **Opções consideradas**:
  - **A**: `git init` local + `git push --force` → perde o commit inicial do remoto.
  - **B**: Clonar remoto para pasta temporária + copiar arquivos atuais por cima + commitar normalmente. ✅
  - **C**: `git init` local + adicionar remote + fetch + merge `--allow-unrelated-histories` → operacionalmente complexo.
- **Decisão**: Opção B. Clone para `$env:TEMP\clarividenciarpg-deploy\`, sobrescrita preservando `.git`, commit + push.
- **Status**: Executado. Commit `9ec0fe7` adicionado a `main` (anterior: `f3cc300`).
- **Consequências**:
  - Histórico do remoto preservado (2 commits no `main` agora).
  - `.gitignore` atualizado para ignorar conteúdo de `uploads/*` mas preservar estrutura via `.gitkeep`.
  - Pasta de trabalho `c:\Users\Usuario\Downloads\clarividencia rpg\` continua **sem** `.git/` — operações git acontecem no clone temporário. Para uso permanente, pode-se clonar para `c:\Users\Usuario\repos\clarividenciarpg\` e trabalhar diretamente lá (decisão deixada para sessão futura).
- **Notas**:
  - Git Credential Manager autenticou via OAuth no navegador na primeira tentativa de push, sem necessidade de Personal Access Token manual.
  - Dois arquivos MP3 extras foram versionados acidentalmente (`freesound_community-rpg-dice-rolling-95182.mp3`, `u_qpfzpydtro-dice-142528.mp3`) — provavelmente sobras de testes. Limpeza para próximo commit.

---

## Decisão 015: Auditoria de acentos no UI (todos os textos visíveis)
- **Contexto**: Os textos visíveis estavam sem acento (`HISTORICO`, `BESTIARIO`, `DOSSIE`, `LOCALIZACAO`, `AMEACA`, `INVOCACAO`, etc.) — provavelmente herança de tempos onde caracteres não-ASCII em arquivos PHP davam dor de cabeça com encoding. Hoje (UTF-8 estável em todos os arquivos + `header Content-Type: charset=UTF-8`), isso é desnecessário e prejudica leitura.
- **Decisão**: Auditar `Grep` em palavras-alvo e corrigir todos os textos *visíveis ao usuário* — títulos, subtítulos, labels, mensagens de erro, ajudas, badges. **Não** mexer em: nomes de classe CSS, IDs, valores de ENUM no banco, nomes de constante PHP (esses são identificadores, não UI).
- **Status**: Implementado. Cobertura: index.php, npcs/*, criaturas/*, rolagem/*, historico/*, NpcValidador, CriaturaValidador, views/*.
- **Consequências**: Leitura natural em português. Risco zero — strings literais não afetam comportamento. Próximas adições devem seguir o mesmo padrão.

---

## Decisão 016: Cropper de imagem 1:1 — vanilla canvas (sem libs)
- **Contexto**: Brief pediu "ver a previsualização da foto e recortar a imagem em proporção de 1:1". O projeto tem disciplina de zero dependências de runtime — não usar Cropper.js, Croppie ou similar.
- **Primeira tentativa (descartada)**: `<img>` com `position: absolute` + `transform: translate()` + máscara via `box-shadow: 0 0 0 9999px`. Bug: a sombra é cortada pelo `overflow: hidden` do palco, então o efeito visual de "moldura escura" não existia. Pior: os transforms acumulados deixavam a imagem flutuar fora do palco. Reportado pelo usuário como "imagem solta na tela, podendo ser mexida pra qualquer lugar".
- **Decisão**: Reescrita **canvas-only**. A imagem nunca existe como elemento DOM — vive apenas como pixels desenhados num `<canvas 320×320>` via `ctx.drawImage()`. Estado simples: `{ img, scale, offsetX, offsetY }`. Drag move o offset, slider/wheel altera scale, redesenha. Ao submeter, desenha em canvas off-screen 800×800 e substitui `input.files` por File JPEG via `DataTransfer`.
- **Status**: Implementado em `assets/js/cropper.js`. Aplicado em campanhas, agentes, NPCs e criaturas.
- **Consequências**:
  - Impossível imagem "vazar" — canvas tem dimensão fixa e pixels fora são descartados pelo próprio canvas.
  - `clampOffsets()` impede mostrar bordas pretas além da imagem.
  - Saída padronizada: JPEG 800×800 q=0.9 (~50-150 KB).
  - Componente reutilizável: `<div data-cropper data-cropper-input="foto" [data-cropper-existing="..."]>` envolvendo o `<input type="file">`.

---

## Decisão 017: `.htaccess` de uploads — bug de sintaxe Apache
- **Contexto**: Após implementar upload de fotos, *todas* as URLs `/uploads/...` retornavam **HTTP 500**. As fotos eram salvas no disco e os nomes no banco corretamente — só o Apache se recusava a servir.
- **Diagnóstico**: log de erros do Apache (`C:\xampp\apache\logs\error.log`) reportou `<FilesMatch> was not closed at /uploads/.htaccess:18`. O arquivo tinha 4 linhas no formato compacto: `<FilesMatch "\.png$"> ForceType image/png </FilesMatch>` na mesma linha. **Apache 2.4 não aceita** abrir e fechar `<FilesMatch>` na mesma linha — o parser fica perdido.
- **Decisão**: Reescrever cada `<FilesMatch>` em três linhas (abrir / diretivas / fechar). Manter as outras políticas (`Require all denied` em `.php`, `php_flag engine off`, `Options -Indexes`).
- **Status**: Corrigido. URLs voltaram a HTTP 200 com `Content-Type` correto.
- **Consequências**: Aprendizado registrado — `<FilesMatch>` e `</FilesMatch>` precisam estar em linhas separadas SEMPRE.

---

## Decisão 018: Fotos exibidas em todos os perfis
- **Contexto**: O brief original previa fotos em campanhas, agentes, NPCs e criaturas. Estava implementado parcialmente: campanha (capa) e agente (formulário) tinham upload, mas NPCs e criaturas não. Visualizações de NPC/Criatura também não exibiam foto.
- **Decisão**: Universalizar:
  - Adicionar upload + cropper em `npcs/formulario.php` e `criaturas/formulario.php`.
  - Atualizar `NpcRepositorio` e `CriaturaRepositorio` para aceitar `foto_arquivo` (preservando coluna se a chave não estiver presente em `$dados` — protege contra apagar foto numa edição que não envia novo arquivo).
  - Criar `criaturas/visualizar.php` (não existia).
  - Adicionar foto no header do dossiê em `npcs/visualizar.php` e nos cards de listagem (`cartao-npc__foto`, `cartao-criatura__foto`).
- **Status**: Implementado. Schema já suportava (`foto_arquivo VARCHAR(160)` desde a migration_003).
- **Consequências**: Fotos aparecem em listagens (thumbnail 1:1) e perfis (foto grande). UploadHelper genérico cobre as 4 subpastas (campanhas/agentes/npcs/criaturas).

---

## Decisão 019: Hero exibida apenas na primeira visita ou em F5
- **Contexto**: A Hero rodava a cada navegação para `/index.php`, incluindo cliques em "VOLTAR AO PAINEL". Para uso real do site, isso virou ruído.
- **Decisão**: Gatear a Hero por **tipo de navegação** + flag em `sessionStorage`:
  - Primeira visita (sessionStorage vazio) → roda
  - F5/Ctrl+R (`navType === 'reload'` via Performance Navigation API) → roda **mesmo se a flag estiver setada**
  - Link interno (`navType === 'navigate'`, flag setada) → pula
  - Botão voltar/avançar (`navType === 'back_forward'`) → pula
- **Status**: Implementado em `assets/js/hero.js` na função `deveRodarHero()` com fallback para a API legada `performance.navigation`.
- **Consequências**: Animação memorável na entrada e ao recarregar (debugging), mas não atrapalha navegação cotidiana. `sessionStorage` zera ao fechar a aba — abrir nova aba conta como primeira visita.

---

## Decisão 020: Multi-dado liberado para todos os tipos
- **Contexto**: Apenas o d20 suportava NdX (regra Ordem Paranormal). d4, d6, d8, d10, d12 e d100 estavam travados em 1 dado. Brief solicitou: "quero que o usuário também possa selecionar mais de um 1d4, 1d6 ... deve apresentar todos os números".
- **Decisão**:
  - **d20** mantém regra OP (vantagem/desastre, escolhe um valor)
  - **d4..d100** com quantidade > 1: rola N dados independentes, **resultadoFinal = SOMA**, todos os valores aparecem na UI
  - Crítico/Desastre permanecem **exclusivos do d20** — sum aleatória que cair em 20 NÃO é "crítico"
  - Migration 004 sobe `resultado_final` de `TINYINT UNSIGNED CHECK 1..100` para `SMALLINT UNSIGNED CHECK 1..2000` (10d100 = 1000)
  - Form: campo "quantidade" sempre visível; ajuda contextual via `[data-ajuda-d20]` / `[data-ajuda-outros]`; min=0 só para d20, demais min=1
- **Status**: Implementado em `dados.js`, `rolagem/api.php`, `rolagem/index.php`, `historico/listar.php` + migration_004.
- **Consequências**: Sistema agora atende rolagens de RPG comuns (4d6 para atributos, 8d10 para dano, etc.). resultadoFinal armazenado é a soma; brutos individuais permanecem em JSON.

---

## Decisão 021: Áudio em camadas para multi-dado + corte preciso no resultado
- **Contexto**: Inicialmente o sistema escolhia 1 dos 3 sons baseado na quantidade. Usuário pediu: "se mais de 1 dado for rolado, todos os sons devem disparar juntos, dando a ideia de vários dados rolando". Adicionalmente: "som deve começar no clique e acabar quando o número aparece".
- **Decisão**:
  - Sons agora **empilham** em camadas (não substituem):
    - 1 dado → `[som_para_as_rolagens]`
    - 2-4 dados → `[som_para_as_rolagens, som_para_rolagem_multipla]`
    - 5+ dados → `[som_para_as_rolagens, som_para_rolagem_multipla, som_para_rolagem_com_muitos_dados]`
  - **Calibragem manual de volume por arquivo** via constante `VOLUMES.*` (rolagem 0.55 / multipla 0.50 / muitos 0.50). Os 3 .mp3 têm peak/RMS diferentes e reencodar é invasivo — calibragem em JS resolve sem tocar nos arquivos.
  - **FadeOut encurtado de 450ms para 80ms**: o som termina exatamente no instante em que o número aparece. Fade rápido evita "click" digital de corte seco.
- **Status**: Implementado. `tocarAudioRolagem` virou `tocarAudiosRolagem` (plural) que retorna array. Estado `audioAtivo` virou `audiosAtivos` (array). Loop sobre o array para tocar/fadear todos.
- **Consequências**: Sensação cinematográfica de coro de dados batendo. Volumes ajustáveis pelo usuário sem touch nos MP3.

---

## Decisão 022: Bug de closure em `tocarAudioRolagem` — funções stateful precisam estar dentro do escopo
- **Contexto**: Após a refatoração para 3 áudios, o submit handler começou a falhar silenciosamente — sem ticker, sem animação, sem fetch. A causa: `tocarAudioRolagem` foi definida no escopo da IIFE (fora do `DOMContentLoaded`), mas referenciava `audioRolagemMulti` etc. — variáveis declaradas DENTRO do callback. ReferenceError em handler async é particularmente cruel: vira promise rejection silenciosa, código depois da chamada nunca executa.
- **Decisão**: Funções **stateful** que precisam ler closures locais devem ser declaradas dentro do `DOMContentLoaded`. Funções **stateless** (que recebem tudo por parâmetro) podem ficar no escopo da IIFE.
- **Status**: `tocarAudioRolagem` movida para dentro do `DOMContentLoaded`. `criarAudio` e `fadeOutAudio` ficam fora (são puras).
- **Consequências**: Lição registrada para futuros refactors. Padrão a seguir: se a função usa variáveis do escopo do handler, ela mora ali junto.

---

## Decisão 023: Refatoração visual via design system (Claude Design)
- **Contexto**: Após o MVP v0.2 estar estável, foi gerado um design system completo no Claude Design (claude.ai/design) para refinar a estética do produto. O bundle de 32 arquivos (~233 KB) foi extraído em `_design_import/clarivid-ncia-paranormal-design-system/` e contém: logos SVG (3 variantes), sprites SVG novos (`dice-icons.svg`, `elementos-icons.svg`), `d20-hero.svg` estático, `colors_and_type.css` (extração documentada do nosso `:root` + tokens novos), protótipos HTML em `preview/` e `ui_kits/painel/index.html`, componentes JSX como referência visual, hero standalone em `components/hero_d20.html` (487 linhas, com geometria 3D real do icosaedro).
- **Descoberta crítica**: `diff -q` confirmou que o `assets/terminal.css` deles é **idêntico** ao nosso — o "design system" é uma **curadoria documentada** do nosso CSS atual, NÃO uma reforma do código. O que é genuinamente novo: 6 SVGs, tokens novos no `colors_and_type.css`, paleta envenenada page-scoped no `index.html` do painel, e o hero standalone com renderização 3D dinâmica.
- **Decisão**: Integrar em **7 passos sequenciais** documentados em `INTEGRACAO_DESIGN.md`:
  1. **Logo + hambúrguer** (logo stacked no painel hambúrguer aberto)
  2. **Ícones SVG dos elementos** (sprite local, sem FontAwesome — ver D024)
  3. **Sprite de dados refit** (só contorno externo — ver D026)
  4. **Cards de criatura** (background metálico + tipografia hierárquica)
  5. **Background contínuo + paleta envenenada global** (ver D025)
  6. **Painel do Mestre em 5 seções cinematográficas** (com sparkline 7d, sussurros do outro lado, atalhos)
  7. **Hero D20 cinematográfica** (geometria 3D real, paragem matematicamente exata na face 20)
- **Status**: Implementado nos commits `2f21aca` (Passo 1), `efc584c` (P2), `4232911` (P3), `9af1eec` (P4), `abef18b` (P5), `1765f3e` (P6), `37cc563`/`c9ba77f`/`c935acd`/`f5acea3`/`b1fe3e5` (P7 + refinamentos da Hero).
- **Consequências**: O projeto deixa de ter estética "MVP funcional" e ganha presença diegética cinematográfica. Decisões 024-030 documentam aspectos específicos. Pasta `_design_import/` arquivada (não foi versionada — `.gitignore` cobre).

---

## Decisão 024: Ícones de elementos — SVG local em vez de FontAwesome
- **Contexto**: O design system trouxe DOIS caminhos para a tag de elemento (Sangue/Morte/Conhecimento/Energia/Medo) nos cards de criatura: (a) sprite local `elementos-icons.svg` com 5 `<symbol>` de 24×24 stroke-only, e (b) protótipos HTML em `preview/cards_criatura.html` e `ui_kits/painel/index.html` que importam Font Awesome 6 via CDN (`fa-droplet`, `fa-skull`, `fa-eye`, `fa-bolt`, `fa-hurricane`).
- **Decisão**: Usar o **SVG local** (`elementos-icons.svg`) — copiado para `assets/img/elementos-icons.svg` e referenciado via `<svg><use href="#el-X"/></svg>`.
- **Razões**:
  - Disciplina de "zero dependências de runtime" (claude.md §2) preservada
  - 2 KB inline vs ~30 KB de CSS+font do FA via CDN
  - `currentColor` integra perfeitamente com vars CSS de elemento (`--el-sangue`, etc.)
  - Consistente com o pattern já estabelecido (sprite SVG dos dados em `rolagem/index.php`)
  - Permite `<title>` semântico dentro de cada `<symbol>` para a11y
  - Sem FOIT/FOUT da CDN
  - **Detalhe decisivo**: `project/README.md` do próprio design system declara literalmente "There is no Font Awesome" na seção "Iconography" — os protótipos HTML que usam FA são **inconsistentes com a própria documentação** (provável shortcut do designer). O `elementos-icons.svg` foi entregue justamente como caminho canônico.
- **Status**: Implementado no Passo 2 (commit `efc584c`).
- **Consequências**: Sprite incluído via `readfile()` no `views/cabecalho.php` uma vez por página. CSS `.tag-elemento` com 5 variantes + drop-shadow do glow. Glifos: gota / caveira / triângulo+olho / raio / espirais concêntricas. `criaturas/listar.php` e `criaturas/visualizar.php` usam `<svg><use>` em vez de texto.

---

## Decisão 025: Paleta envenenada global no `:root` (não scoped via `.app`)
- **Contexto**: O design system define uma "paleta envenenada" (cores envelhecidas/dessaturadas) no `ui_kits/painel/index.html` dentro de um bloco `.app { --gold: #e0b53d; ... }` — implementação page-scoped. Considerei manter assim (refit gradual, classes opcionais por página) vs reformar diretamente o `:root` global.
- **Decisão**: **Reforma direta no `:root`** do `terminal.css`. Paleta envenenada vira a paleta canônica do projeto.
- **Razões**:
  - O projeto tem **uma única "app"** — scoping seria proteção desnecessária
  - Refit gradual cria duas paletas em paralelo, gera ambiguidade visual durante a transição
  - Passo 5 (refinamentos) foi agendado **antes** do Passo 6 (Painel do Mestre), então a reforma estaria completa quando os componentes fossem refatorados
- **Mapeamento aplicado**:
  - `--el-conhecimento`: `#ffd60a` (neon) → `#e0b53d` (mostarda envelhecida)
  - `--el-energia`: `#9d4edd` (neon) → `#7b4d9e` (roxo cinza dessaturado)
  - `--el-sangue`: `#c8102e` → `#a53846` (sangue antigo)
  - `--critico`: `#00ff66` → `#5fa873` (fosforescente apagado)
  - `--el-morte`, `--el-medo`, `--falha`: mantidos (sem override no design system)
  - Glows recalculados com novos rgba; helpers novos `--gold-dim` e `--ink-paper`
- **Status**: Implementado no Passo 5 (commit `abef18b`).
- **Consequências**: Todos os componentes que usam vars de elemento herdam a nova paleta automaticamente. Auditoria de contraste WCAG AA mantida. rgba dos `@keyframes piscar-energia` foi atualizado em `terminal.css` para alinhar com novo roxo (Passo 2 commit também).

---

## Decisão 026: Sprite de dados — IDs `geo-d*` preservados ao copiar do design system
- **Contexto**: O design system trouxe `dice-icons.svg` com 7 `<symbol>` (d4..d100) + sigil. Os IDs do sprite deles são `d4`, `d6`, ..., `d100` — sem prefixo. Nosso código atual em `rolagem/index.php` e `assets/js/dados.js` referencia o sprite via `<use href="#geo-dN">` (com prefixo `geo-`), conforme Decisão 008.
- **Decisão**: Ao copiar o sprite para `assets/img/dice-icons.svg`, **renomear os IDs** de `dN` para `geo-dN` via regex no PowerShell antes de salvar. Mantém compat total com `dados.js` sem precisar alterar uma linha de JS.
- **Justificativa**: ADR 008 canonizou o prefixo `geo-` para distinguir o sprite geométrico de outros possíveis sprites futuros (`#el-sangue`, `#geo-d20`, etc — namespacing semântico). Quebrar essa convenção pelo design system seria inconsistência interna sem ganho.
- **Status**: Implementado no Passo 3 (commit `4232911`). O sprite anterior inline em `rolagem/index.php` (50 linhas com polygons + cross-lines + divisores) substituído por `<?php readfile() ?>` apontando ao novo sprite global. Outer `<text>` redundante removido (sprite novo já inclui label dentro do `<symbol>`).
- **Consequências**: Decisão 011 (tripla defesa `hidden`/`display="none"`/`.is-oculta`) preservada — `dados.js` toggla um SVG separado (`formasSvg` com `<g data-dado="...">`), não o sprite que foi trocado. Sem regressão.

---

## Decisão 027: Tokens novos do `colors_and_type.css` como acréscimo, não substituição
- **Contexto**: Comparação cuidadosa entre o `:root` do design system (`colors_and_type.css`, 135 linhas) e o nosso atual mostrou que **todas as variáveis presentes em ambos são idênticas** (mesmas hex codes, mesmos gradientes, mesmas font stacks). O `colors_and_type.css` **adiciona** novos tokens semânticos que ainda não existiam: spacing scale, type scale, letter-spacing, shadow, animation easing.
- **Decisão**: No Passo 5, **estender** o `:root` do `terminal.css` com os tokens novos. Não tocar nas vars existentes (apenas as 4 cores envenenadas conforme D025). Conflito zero.
- **Tokens novos adicionados ao `:root`**:
  - **Spacing**: `--space-1` (4px) ... `--space-8` (32px) — escala de 7 níveis
  - **Type semantic shorthands**: `--t-h1`, `--t-h2`, `--t-h3`, `--t-display`, `--t-ui`, `--t-label`, `--t-body`, `--t-small`, `--t-mono` (todos no formato `font:` shorthand para uso direto)
  - **Letter-spacing**: `--ls-tight`, `--ls-base`, `--ls-ui`, `--ls-label`, `--ls-caps`, `--ls-wide`
  - **Shadow**: `--shadow-card`, `--shadow-lift` (com gold envenenado), `--shadow-inset`
  - **Animation**: `--ease-standard`, `--ease-overshoot`, `--t-fast` (150ms), `--t-base` (220ms), `--t-slow` (380ms)
- **Status**: Implementado no Passo 5 (commit `abef18b`).
- **Consequências**: Componentes novos (cards de criatura no Passo 4, faixa de stats no Passo 6) usam esses tokens. Componentes legados continuam funcionando com hardcoded values — refit pode acontecer organicamente quando cada componente for tocado.

---

## Decisão 028: Cormorant Garamond + IM Fell English com escopo restrito
- **Contexto**: O design system extraído carrega **2 fontes adicionais** via Google Fonts no `ui_kits/painel/index.html`: `Cormorant Garamond` (italic, usada em citações com `❝...❞` no `.hero__sub`) e `IM Fell English` (subtítulos poéticos). Não constam nos 4 layers tipográficos canônicos (Cinzel/Montserrat/Helvetica/JetBrains Mono — ADR 004).
- **Decisão**: Adicionar ambas com **escopo de uso restrito**:
  - **Cormorant Garamond** → APENAS em "Sussurros do Outro Lado" e citações italic do Painel (ex: `.painel-hero__sub`, `.ultima-critica__desc`)
  - **IM Fell English** → APENAS em subtítulos poéticos diegéticos (ex: `.sussurros__poetico`)
  - Carregadas via Google Fonts no `<link>` de `views/cabecalho.php` (mesma estratégia das outras 4 — ADR 004)
  - **NÃO** promovidas para `--titulo` ou `--corpo` globais; criadas vars específicas `--font-sussurro` (Cormorant) e `--font-poetica` (IM Fell)
- **Razão**: Manter o sistema tipográfico de 4 famílias limpo e consistente para componentes gerais. Usar essas duas extras como "tinta de cena" só onde a atmosfera narrativa pede — preserva impacto.
- **Status**: Implementado no Passo 6 (commit `1765f3e`).
- **Consequências**: 6 famílias carregadas no total agora (4 originais + 2 de cena). Tamanho do request Google Fonts aumenta marginalmente (~30 KB extras). Se algum dia surgir `--font-narrativa` ou similar, pode entrar pelo mesmo padrão.

---

## Decisão 029: Botão "// INICIAR" antes da animação da Hero (resolve política de autoplay)
- **Contexto**: A Hero D20 dispara áudio (`som_para_a_hero.mp3`) sincronizado com a queda do dado em t=0 da timeline. A política de autoplay dos browsers (Chrome ≥66, Firefox ≥66, Safari ≥11) bloqueia `audio.play()` automático antes de gesto humano — Promise rejeita com `NotAllowedError`. Em first-visit, o som da queda ficava perdido.
- **Tentativa rejeitada**: instalar `document.addEventListener('click', ativar, { once: true })` para liberar o áudio no primeiro click do usuário em qualquer lugar. Funcionou para o LOOP ambiente (que dispara em t=3.7s), mas NÃO para o áudio de queda (que dispara em t=0 — quando o click chega, a queda já passou). Desync permanente entre visual e som da queda.
- **Decisão**: Introduzir um **gate de gesto humano** ANTES da animação. Página carrega com:
  - Dado D20 em pose **RESTING estática** (com leve hover senoidal)
  - Bloco centralizado "// AUTORIZAR RITUAL" + botão "// INICIAR"
  - Sem áudio tocando, sem console warnings
  - Click em "// INICIAR" → audio.play() chamado dentro do user-gesture context → autoplay liberado para a sessão → fade-out do bloco (200ms) → `dispararAnimacao()` com `await audioQueda.play()` antes do RAF inicial → sincronia frame-a-frame perfeita
- **Razões**:
  - Audio + visual queda 100% sincronizados na primeira visita (não só após F5)
  - Não há mais janela "silenciosa" durante a queda na primeira interação
  - Console limpo (sem warnings de autoplay block)
  - Fallback `document.addEventListener('click')` do loop pode ser removido (gesto humano direto resolve)
  - Texto "AUTORIZAR RITUAL" mantém vibe diegética (consentimento ritual)
- **Trade-off aceito**: +1 click necessário para ver a animação. Aceitável dado que é entrada do site (usuário esperava interação) e que o ganho de UX (audio + visual sincronizados) compensa.
- **Status**: Implementado nos commits `b1fe3e5` (botão final) + `c935acd` (loop ambiente) + `c9ba77f` (sincronia 3.2s).
- **Consequências**: Estilo do botão "// INICIAR" idêntico ao "// ROMPER O VÉU" (mesma família visual de UI). Logs diagnósticos `[Hero] ...` no console permitem debug sem necessidade de instrumentação adicional. Lição L1 de `INTEGRACAO_DESIGN.md` referencia esta decisão como solução final.

---

## Decisão 030: `body.hero-ativa { overflow: hidden }` + Esc/wheel como acionadores alternativos
- **Contexto**: A Hero está em `position: fixed` cobrindo a viewport, mas o conteúdo do Painel do Mestre já está renderizado no DOM por baixo (para ficar acessível imediatamente após o "// ROMPER O VÉU"). Resultado colateral: a scrollbar lateral aparecia durante a Hero, criando ruído visual durante o momento mais cinematográfico do produto.
- **Decisão**:
  - CSS: `body.hero-ativa { overflow: hidden }` — uma regra simples
  - JS: `document.body.classList.add('hero-ativa')` quando a Hero monta (logo após passar pelo gate Decisão 019)
  - JS: `document.body.classList.remove('hero-ativa')` no setTimeout de 950ms após `.is-saindo` (junto com `display: none`) — libera scroll exatamente quando a Hero some
  - **Bonus**: Esc + scroll-down (wheel deltaY > 0) como **acionadores alternativos** do botão "// ROMPER O VÉU", liberados após a flag `_podeFinalizar` ser setada em t=5.8s (botão clicável + 0.8s fade-in)
- **Razões**:
  - Scrollbar lateral durante a Hero quebra a imersão — overlay deve ser totalmente isolado
  - Esc como atalho de fechar é UX padrão (modal-like)
  - Scroll-down sinaliza intenção de "ver o conteúdo abaixo" — alinhado com a metáfora "romper o véu"
  - `_podeFinalizar` impede que gestos prematuros (durante a animação) interrompam o show
- **Status**: Implementado no commit `f5acea3`.
- **Consequências**: `wheel` listener com `{ passive: true }` para não bloquear o scroll engine. Listeners ficam ativos após `_heroFinalizado` (já bloqueia internamente — não precisa removeEventListener para limpeza). Nenhum impacto de performance.
