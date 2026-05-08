# Painel de Investigação e Gestão de Entidades: Clarividência Paranormal


##  Visão Geral do Projeto
**Clarividência Paranormal** é uma aplicação web de suporte para Mestres e Jogadores do sistema de RPG Ordem Paranormal.
A proposta central é funcionar como um artefato digital de investigação, ajudando mestres a:
Gerenciar fichas de NPCs e Criaturas de forma ágil
Automatizar rolagens de dados com regras complexas do sistema
Manter um registro histórico da narrativa (logs de eventos)
Visualizar ameaças de acordo com seus elementos do "Outro Lado"
O sistema cruza:
Dados de atributos de agentes e monstros
Mecânicas específicas de dados (escolha do maior valor)
Persistência em banco de dados para continuidade da campanha
Identidade visual imersiva para o clima de horror e investigação


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


## Funcionalidades do MVP
**1. Dashboard (O Terminal)**
Visão geral da sessão
Atalhos para rolagens rápidas de dados
Exibição dos últimos registros do log


**2. Gerenciador de NPCs**
Cadastro completo (Nome, ocupação, localização, história)
Classificação por Atitude (Amigável, Neutro, Hostil)
Busca e filtragem rápida


**3. Bestiário Paranormal**
Cadastro de Criaturas (Nome, VD, PV, Habilidades)
Vinculação Elemental: Definição do elemento (Sangue, Morte, Energia ou Conhecimento)
Interface dinâmica: o card da criatura muda de cor e estilo com base no elemento selecionado


**4. Lançador de Dados "Clarividente"**
Lógica customizada: Rola n dados e destaca automaticamente o maior resultado
Suporte a Atributo 0 (rola 2 dados e pega o menor)
Animação SVG/CSS: Feedback visual do dado em movimento


**5. Histórico de Missão (Logs)**
Persistência de todas as rolagens no banco de dados
Lista cronológica de eventos com carimbo de tempo
Possibilidade de deletar ou editar registros antigos


##  Design e Experiência
**Paleta de cores (Elementos do Medo):**
Sangue: Vermelho vivo e sombras pulsantes
Morte: Tons de cinza, preto e espirais
Conhecimento: Dourado/Amarelo e papel antigo
Energia: Roxo neon e efeitos de glitch


**Princípios de UX:**
Interface "Dark Mode" para não cansar a vista em sessões noturnas
Mensagens de sistema temáticas (Ex: "Manifestando resultado...")
Navegação fluida entre fichas de monstros e o lançador de dados


##  Stack Tecnológica
Linguagem: PHP 8.4+ (Back-end robusto e seguro)
Banco de Dados: MySQL (Persistência via PDO)
Interface: HTML5 / CSS3 (Design Responsivo e Temático)
Lógica: JavaScript Vanilla (Interações em tempo real)
IA de Apoio: Claude Code (Opus 4.7) + Open Agent Skills (UI/UX Taste)


## Fluxo do Usuário
Acessa o Clarividência Paranormal
Cadastra os NPCs e Monstros da sessão atual
Inicia o combate ou investigação
Clica nos botões de rolagem conforme a necessidade narrativa
O sistema salva o histórico e exibe o resultado animado
O mestre consulta o Log ao final para resumir a sessão


##  Limitações do MVP
Não inclui ficha completa de jogadores (foco em NPC/Criatura)
Sem suporte a áudio/trilha sonora (foco em dados e texto)
Banco de dados local para o trabalho acadêmico (localhost)


##  Objetivo do Produto
Fornecer ao mestre de Ordem Paranormal uma ferramenta que não pareça um "software de escritório", mas sim um componente da própria história.


##  Resumo Final
Clarividência Paranormal transforma a matemática complexa do RPG em uma interface intuitiva e mística, garantindo que o medo e a investigação sejam os únicos focos da mesa, enquanto o PHP e o JavaScript cuidam das regras por trás do véu.
