# Painel de Investigação e Gestão de Entidades: Clarividência Paranormal

> **Nota (08/05/2026):** este brief reflete o estado **atual** do produto.
> A evolução desde o MVP v0.1 está documentada em `decisoes.md` e `logs.md`.
> Algumas funcionalidades antes listadas como "limitações" do MVP já foram
> entregues nas fases 2-4 (campanhas, ficha de agente, áudio).


##  Visão Geral do Projeto
**Clarividência Paranormal** é uma plataforma web (VTT — Virtual Tabletop)
para o sistema de RPG **Ordem Paranormal**. Funciona como um artefato
digital de investigação, ajudando mestres a:

- Gerenciar **campanhas** com capa, descrição e entidades vinculadas
- Manter fichas de **agentes** (PJs), **NPCs** e **criaturas** com fotos
- Automatizar rolagens de dados (d4..d100) com regras canônicas
- Manter um histórico cronológico da narrativa
- Visualizar ameaças de acordo com seus elementos do "Outro Lado"

O sistema cruza:
- Dados de atributos de agentes e monstros
- Mecânicas específicas de dados (vantagem/desastre/multi-dado)
- Persistência em banco de dados para continuidade da campanha
- Identidade visual imersiva para o clima de horror e investigação


##  Problemas que o Produto Resolve
O projeto foi desenhado para mitigar as dificuldades comuns em mesas de RPG de alta complexidade:
**Principais dores:**
Lentidão no combate para consultar fichas de monstros
Erros de cálculo em rolagens de múltiplos dados (Ex: 3d20 e pegar o maior)
Perda de histórico de acontecimentos importantes da sessão
Desorganização de NPCs (personagens secundários) e suas motivações
Falta de imersão visual durante o uso de ferramentas digitais genéricas
Impacto dessas dores:
Quebra do ritmo narrativo e do suspense
Frustração dos jogadores com pausas técnicas longas
Dificuldade do mestre em gerenciar múltiplos inimigos simultâneos


##  Proposta de Valor
O Clarividência Paranormal oferece:
Uma interface mística e funcional que transforma a gestão técnica da mesa em parte da experiência narrativa de investigação paranormal.
Benefícios principais:
Consultas a criaturas em segundos
Rolagens automáticas e animadas
Histórico de "Registros de Percepção" salvo permanentemente
Estética que auxilia na imersão do tema Noir/Horror


##  Público-Alvo
Perfil demográfico:
Comunidade de RPG (especialmente fãs de Ordem Paranormal)
Jovens e adultos interessados em tecnologia e narrativa
Perfil comportamental:
Mestres de RPG que buscam organização digital
Jogadores que preferem interfaces temáticas a planilhas genéricas
Grupos que realizam campanhas de longa duração (necessidade de persistência)


##  Conceito do Produto
O app funciona como um:
Terminal da Ordem / Artefato Ritualístico
Fluxo:
O Mestre cadastra as ameaças (Bestiário) e aliados (NPCs)
Durante a sessão, o mestre utiliza o painel para rolar testes de perícia
O sistema processa a lógica de dados e exibe o resultado com impacto visual
Cada ação relevante é registrada no "Log de Clarividência" para consulta futura


## Funcionalidades (estado atual — v0.2)

**0. Hero / Splash Screen**
- d20 SVG 3D com 11 faces visíveis sombreadas, gira 1.8s e assenta no 20
- 3 ondas de choque concêntricas (dourado + sangue + flash radial)
- Áudio sincronizado com a animação (`som_para_a_hero.mp3`)
- Roda apenas na **primeira visita** ou em **F5** (não em cliques internos)


**1. Dashboard (O Painel do Mestre)**
- 4 indicadores numéricos (campanhas, agentes, dossiês, ameaças, rolagens)
- Grade de 6 módulos com status operacional
- Lista lateral das 5 últimas rolagens registradas


**2. Gerenciador de Campanhas**
- CRUD completo com upload de capa (cropper 1:1 vanilla canvas)
- Subqueries de contagem mostram agentes/NPCs/criaturas vinculados
- Descrição livre, sistema (default "Ordem Paranormal"), capa opcional


**3. Ficha de Agente (PJ) Completa**
- 9 seções colapsáveis em `<details>` HTML5: Identidade, Barras
  (PV/SAN/PE), Atributos (FOR/AGI/INT/VIG/PRE), Defesa, Narrativa,
  Perícias (26 canônicas com 4 graus), Combate (ataques calculados
  em tempo real), Inventário (com tracking de espaços), Rituais
- Salvamento transacional (BEGIN/COMMIT/ROLLBACK) com tabelas filhas
- Foto recortada em 1:1 via cropper canvas


**4. Gerenciador de NPCs**
- Cadastro completo (Nome, ocupação, localização, história, foto)
- Classificação por Atitude (Amigável, Neutro, Hostil)
- Busca textual em nome/ocupação/história + filtros por atitude/localização
- Vinculação opcional a uma campanha
- Dossiê individual (visualizar.php) com layout de "arquivo da Ordem"


**5. Bestiário Paranormal**
- Cadastro de Criaturas (Nome, VD, PV, Habilidades, foto)
- Vinculação Elemental: Sangue, Morte, Conhecimento ou Energia
- Interface dinâmica: card pulsa/glita por elemento, glow específico
- Vinculação opcional a uma campanha
- Perfil individual (visualizar.php) por criatura


**6. Lançador de Dados (Ritual de Clarividência)**
- 7 tipos de dado com **ícones geométricos SVG canônicos**:
  d4 (pirâmide), d6 (cubo), d8 (octaedro), d10 (pentágono duplo),
  d12 (dodecaedro), d20 (icosaedro), d100 (esfera facetada)
- **d20**: regra Ordem Paranormal — N≥1 pega o MAIOR (vantagem),
  N=0 rola 2 e pega o MENOR (desastre); brilho em 1 (falha) e 20 (crítico)
- **d4..d100**: rola N dados independentes, **TODOS exibidos**, soma
  registrada como resultado
- **Áudio em camadas** sincronizado (1 dado = 1 som; 2-4 = 2 sons
  empilhados; 5+ = 3 sons), com calibragem de volume por arquivo
- Cooldown de 1.3s no botão para impedir spam


**7. Histórico de Missão (Diário de Campanha)**
- Persistência de todas as rolagens no banco com tipo, quantidade,
  brutos (JSON), resultado final, flags de crítico/desastre
- Lista cronológica em tabela; vira **cards** no mobile via `data-label`


##  Design e Experiência
**Paleta dos 5 Elementos do Outro Lado (canônica Ordem Paranormal):**
- **Sangue** (`#c8102e`) — vermelho profundo, pulsa em ameaças hostis
- **Morte** (`#d8d8d8`) — branco pálido, usado em títulos e silêncio
- **Conhecimento** (`#ffd60a`) — dourado, accent principal da UI
- **Energia** (`#9d4edd`) — roxo neon, glitch em criaturas anômalas
- **Medo** (`#5a1d8a`) — púrpura escuro, atmosfera de fundo (gradiente
  radial sutil no `body`)


**Tipografia em 3 camadas:**
- **Cinzel** (Google Fonts) — títulos majores, vibe ritualística
- **Montserrat** — UI, botões, labels, navegação
- **Helvetica/Arial** — corpo de texto longo
- **JetBrains Mono** — IDs, timestamps, prompts (`>`)


**Princípios de UX:**
- Interface "Dark Mode" obsidiana para sessões noturnas
- Mensagens temáticas ("Consultando o Outro Lado...", "Manifestação")
- Menu hambúrguer pure-CSS com glassmorphism (sempre visível)
- Responsividade em 4 breakpoints (1024 / 768 / 540 / 380)
- Tabela do histórico vira cards no mobile


##  Stack Tecnológica
- **Back-end**: PHP 8.4+ com `declare(strict_types=1)` em todos os arquivos
- **Banco**: MySQL/MariaDB (XAMPP) acessado via PDO com prepared
  statements reais (`ATTR_EMULATE_PREPARES=false`)
- **Front-end**: HTML5 + CSS3 + JavaScript Vanilla — **zero dependências
  de runtime** (sem jQuery, sem React, sem Tailwind, sem libs de cropper)
- **IA de Apoio**: Claude Code (Opus 4.7) para pair programming


## Fluxo do Usuário
1. Acessa o Clarividência Paranormal pela primeira vez (Hero anima)
2. Cria a campanha em `/campanhas/` com capa
3. Cadastra agentes (PJs), NPCs e criaturas vinculados à campanha
4. Durante a sessão, usa o lançador de dados (`/rolagem/`) para testes
5. Sistema salva tudo no histórico com timestamp
6. Mestre consulta o Diário de Campanha ao final para resumir


##  Limitações conhecidas
- Banco local (XAMPP) — apropriado para uso acadêmico/single-master
- Sem autenticação multi-usuário (todos veem todos)
- Sem modo "público" para jogadores acessarem suas próprias fichas


##  Objetivo do Produto
Fornecer ao mestre de Ordem Paranormal uma ferramenta que não pareça um
"software de escritório", mas sim um componente da própria história —
um *terminal investigativo* que vira parte da mesa.


##  Resumo Final
Clarividência Paranormal transforma a matemática complexa do RPG em uma
interface intuitiva e mística. O PHP e o JavaScript cuidam das regras
por trás do véu enquanto o medo e a investigação tomam o palco — com
campanhas, agentes, NPCs, criaturas, rolagens com áudio em camadas e
fichas completas de personagem em uma única plataforma.
