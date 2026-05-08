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
