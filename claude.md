# Clarividência Paranormal — Guia para Continuação

> Documento mestre de referência. **Leia este arquivo primeiro** ao retomar o
> projeto em uma nova sessão. Ele descreve o estado atual, as convenções,
> e onde encontrar tudo.

---

## 1. Visão Geral

**Clarividência Paranormal** é uma plataforma web (VTT — Virtual Tabletop)
para o RPG **Ordem Paranormal**. Permite que o Mestre gerencie campanhas,
agentes (PJs), NPCs, criaturas e rolagens em um único painel com estética
de "terminal investigativo".

Sistema acadêmico. Stack escolhida intencionalmente sem frameworks
pesados, para mostrar fundamentos.

## 2. Tech Stack

| Camada | Tecnologia |
|---|---|
| Back-end | **PHP 8.4+** com `declare(strict_types=1)` em todos os arquivos |
| Banco | **MySQL** (XAMPP usa **MariaDB 10.x**) acessado via **PDO** com prepared statements reais (`ATTR_EMULATE_PREPARES=false`) |
| Front-end | **HTML5 + CSS3 + Vanilla JS** (zero dependências de runtime) |
| Tipografia | **Cinzel** (títulos), **Montserrat** (UI), **Helvetica** (corpo), **JetBrains Mono** (código/IDs/timestamps). Carregadas via Google Fonts. |
| Servidor local | **XAMPP** (Apache + MariaDB) com DocumentRoot em `C:\xampp\htdocs` |

## 3. Estrutura do Projeto

```
clarividencia rpg/
├── claude.md             ← este arquivo
├── context.md            ← brief original do produto
├── decisoes.md           ← ADRs (Architecture Decision Records)
├── logs.md               ← log cronológico de eventos
├── .gitignore
├── config.php            ← PDO singleton + url() helper + escapar()
├── index.php             ← Painel do Mestre (dashboard) com Hero
│
├── views/                ← partials globais
│   ├── cabecalho.php     ← <head> + topo (marca + menu hambúrguer)
│   └── rodape.php        ← rodapé + carrega validacao.js, hero.js, agente.js
│
├── src/                  ← classes PHP (OOP, sem autoloader)
│   ├── sessao.php        ← iniciarSessao(), CSRF, flash messages
│   ├── UploadHelper.php  ← upload seguro de imagens (finfo, whitelist)
│   ├── PericiaCatalog.php
│   ├── CampanhaRepositorio.php / CampanhaValidador.php
│   ├── AgenteRepositorio.php   / AgenteValidador.php
│   ├── NpcRepositorio.php      / NpcValidador.php
│   ├── CriaturaRepositorio.php / CriaturaValidador.php
│   └── LogRepositorio.php
│
├── campanhas/            ← CRUD de campanhas com upload de capa
├── agentes/              ← Ficha completa do PJ
│   └── partials/         ← 9 partials da ficha (_identidade, _barras, etc.)
├── npcs/                 ← CRUD de NPCs com dossiê + busca textual
├── criaturas/            ← CRUD do bestiário com cores por elemento
├── rolagem/              ← Lançador d4..d100 + endpoint /rolagem/api.php
├── historico/            ← Diário de campanha (rolagens registradas)
│
├── sql/
│   ├── schema.sql                    ← schema consolidado para fresh install
│   ├── migration_002_tipo_dado.sql   ← já aplicado
│   └── migration_003_vtt.sql         ← já aplicado
│
├── assets/
│   ├── css/terminal.css   ← folha única (~3700 linhas após Phase 6, organizada por módulo)
│   ├── js/
│   │   ├── validacao.js   ← validação de form + confirmações + contadores
│   │   ├── dados.js       ← rolagem multi-dado + áudio em camadas
│   │   ├── hero.js        ← splash com geometria 3D real do icosaedro (479 linhas)
│   │   ├── agente.js      ← ficha dinâmica (barras, ataques, listas)
│   │   └── cropper.js     ← cropper 1:1 vanilla canvas (Decisão 016)
│   ├── img/               ← sprites SVG do design system (Phase 6)
│   │   ├── dice-icons.svg       ← 7 dados (`#geo-d4..#geo-d100` + sigil)
│   │   └── elementos-icons.svg  ← 5 elementos (`#el-sangue..#el-medo`)
│   └── audio/
│       ├── som_para_a_hero.mp3                       ← queda da Hero (~3.19s)
│       ├── clarividencia_paranormal_loop.mp3         ← loop ambiente (61s)
│       ├── som_para_as_rolagens.mp3
│       ├── som_para_rolagem_multipla.mp3
│       └── som_para_rolagem_com_muitos_dados.mp3
│
└── uploads/              ← imagens enviadas (não versionadas, exceto estrutura)
    ├── .htaccess         ← bloqueia execução PHP/CGI nesta árvore
    ├── campanhas/.gitkeep
    ├── agentes/.gitkeep
    ├── npcs/.gitkeep
    └── criaturas/.gitkeep
```

## 4. Estado Atual (o que está pronto)

| Fase | Conteúdo | Status |
|---|---|---|
| **1** | Fundações: schema VTT (6 tabelas novas), refresh visual obsidiana, ícones geométricos dos dados (SVG sprite), pasta /uploads/ protegida | ✅ |
| **2** | Hero 2.0 (d20 3D + onda de choque), CRUD de Campanhas com upload | ✅ |
| **3** | Vínculos NPC↔Campanha e Criatura↔Campanha, marca renomeada para "Clarividência Paranormal", paleta refinada (5 elementos com Medo), responsividade mobile (4 breakpoints) | ✅ |
| **4** | Ficha completa de Agente (9 seções modulares, salvamento transacional, JS dinâmico para barras/ataques/listas) | ✅ |
| **5** | Polimento: cropper 1:1 vanilla canvas, fotos em todos os perfis, multi-dado liberado para todos os tipos com soma, áudio em 3 camadas com calibragem manual, Hero apenas em primeira-visita/F5, auditoria de acentos | ✅ |
| **6** | **Refatoração visual via Claude Design (7 passos):** logo stacked + hambúrguer, ícones SVG locais para os 5 elementos (sem FontAwesome), sprite de dados refit (só contorno externo, IDs `geo-d*` preservados), cards de criatura com background metálico, paleta envenenada global no `:root` + grain SVG + tokens novos (spacing/type/animation), Painel do Mestre em 5 seções cinematográficas, Hero D20 com geometria 3D real e botão "// INICIAR" antes da animação. ADRs 023-030 documentam. | ✅ |
| **7** | Pendente. Veja "Backlog" abaixo. | ⏳ |

## 5. Backlog (próximas sessões)

Ordem sugerida (após Phase 6 concluída em 2026-05-09):

1. **AJAX para barras de PV/SAN/PE** — endpoint que persiste mudança de uma coluna isoladamente, sem ter que dar submit no formulário inteiro. Útil durante combate.
2. **Página de visualização da Campanha** com gestão bidirecional de vínculos (assign/remove agentes/NPCs/criaturas pelo lado da campanha).
3. **Cálculo automático de bônus de perícia** conforme regras canônicas: Treinado = +metade do NEX, Veterano = +metade do NEX + 2, Especialista = +metade do NEX + 4. Hoje o usuário escreve `bonus_extra` à mão.
4. **Sistema de iniciativa em combate** (mencionado no brief original, ainda não atacado).
5. **Endpoint de rolagem de perícia/ataque** que use os valores da ficha ao invés do form de rolagem genérico.
6. **Importar/Exportar ficha como JSON** (para salvar/recuperar entre campanhas).
7. **Modo público de campanha** (URL com slug que jogadores podem visitar para ver as próprias fichas).
8. **Normalização real dos MP3 via ffmpeg** (`-af loudnorm`) para parar de depender da calibragem manual em JS.
9. **Painel do Mestre — preencher o que ficou placeholder**: sparkline real para os 4 stats sem histórico (hoje só ROLAGENS_7D tem dados; CAMPANHAS/AGENTES/NPCS/AMEACAS têm hint estático). Pode exigir tracking de criação por dia em colunas `criado_em`.
10. **Audit de glows decorativos** em elementos não-interativos (adiado durante Passo 5 da refatoração visual). A paleta envenenada por si reduziu a intensidade percebida; refinamento pode acontecer organicamente.

## 6. Ambiente local (XAMPP)

### Setup inicial
1. Instalar **XAMPP** com Apache + MariaDB.
2. Pasta de trabalho: `c:\Users\Usuario\Downloads\clarividencia rpg\`
3. Pasta servida pelo Apache: `C:\xampp\htdocs\clarividenciarpg\`
4. Acesso: http://localhost/clarividenciarpg/

### Banco
1. http://localhost/phpmyadmin
2. Importar `sql/schema.sql` (cria DB + todas as tabelas com seeds).
3. **Não precisa rodar migrations** se você importou o `schema.sql` — ele já está consolidado.
4. Migrations só servem para upgrade incremental se você já tinha um banco antigo:
   - `migration_002_tipo_dado.sql` (suporte a d4..d100)
   - `migration_003_vtt.sql` (campanhas, agentes, perícias, etc.)

### Sincronização pasta de trabalho ↔ htdocs

Editamos em **`c:\Users\Usuario\Downloads\clarividencia rpg\`** e copiamos
para `C:\xampp\htdocs\clarividenciarpg\` para testar. Comando rápido em PowerShell:

```powershell
$origem = "c:\Users\Usuario\Downloads\clarividencia rpg"
$destino = "C:\xampp\htdocs\clarividenciarpg"
$itens = Get-ChildItem -Path $origem -Force | Where-Object {
    $_.Name -ne 'context.md' -and $_.Name -ne 'decisoes.md' -and $_.Name -ne 'logs.md'
}
foreach ($item in $itens) {
    Copy-Item -Path $item.FullName -Destination $destino -Recurse -Force
}
```

(Os `.md` de documentação ficam só na pasta de trabalho — não vão pro servidor.)

## 7. Convenções de código

### PHP
- `declare(strict_types=1);` em **todos** os arquivos.
- Classes em PascalCase (`AgenteRepositorio`, `CampanhaValidador`).
- Métodos e variáveis em camelCase (`buscarPorId`, `$campanhaId`).
- PHPDoc nos métodos públicos.
- **Nunca** confiar em input do cliente: validar via `*Validador.php`,
  re-calcular no servidor (ver `rolagem/api.php` que recalcula `eh_critico`).
- **Sempre** usar `escapar()` (htmlspecialchars) ao imprimir dados em HTML.
- **Sempre** usar prepared statements via PDO (zero string concatenation com input).
- CSRF: gerar com `gerarTokenCsrf()` em forms, validar com `validarTokenCsrf()` em handlers POST.

### Caminhos / Links
- **Nunca** usar caminhos absolutos hardcoded (`/algo`).
- **Sempre** usar `url('/algo')` que detecta o sub-diretório de instalação.
- Em JS, ler URL de `data-attribute` no HTML (ex: `form.dataset.api`).

### CSS
- Variáveis em `:root` para tudo (`--canvas`, `--el-sangue`, `--titulo`, etc.).
- BEM para componentes (`.cartao-agente__nome`, `.barra-ficha__preenchimento`).
- Glows como acento (`box-shadow`, `text-shadow`), não como fill permanente.
- Responsividade: 4 breakpoints (1024 / 768 / 540 / 380).

### JS
- IIFE wrapping `(() => { ... })()` para evitar globais.
- `'use strict';` no topo.
- `data-*` attributes para hooks (não classes nem IDs).
- Sair cedo se elementos esperados não existem (`if (!form) return;`).
- `addEventListener` sempre (sem inline handlers).

## 8. Regras de Ordem Paranormal (canônicas no sistema)

### Rolagem de teste de atributo
- Atributo N ≥ 1: rola N d20, mantém o **MAIOR** (vantagem).
- Atributo N = 0: rola 2 d20, mantém o **MENOR** (desastre).
- 20 natural = **Sucesso Crítico** (brilho verde).
- 1 natural = **Falha Crítica** (brilho vermelho).
- Em outros tipos de dado (d4, d6, d8, d10, d12, d100), regra de vantagem
  não se aplica — sempre 1 dado simples. Brilho ainda só em 1 e 20.

### Atributos
FOR / AGI / INT / VIG / PRE — escala 0..6.

### Perícias (26)
Ver `src/PericiaCatalog.php`. Cada perícia tem um atributo base e um grau:
Destreinado / Treinado / Veterano / Especialista.

### Elementos do Outro Lado
Sangue (vermelho), Morte (branco), Conhecimento (dourado), Energia (roxo)
+ Medo (púrpura escuro, "elemento mestre", usado só como aura/fundo).

## 9. Banco — modelo simplificado

```
campanhas (id, nome, sistema, descricao, capa_arquivo, ...)
   ├── 1:N → agentes        (FK campanha_id, ON DELETE SET NULL)
   ├── 1:N → npcs           (FK campanha_id, ON DELETE SET NULL)
   └── 1:N → criaturas      (FK campanha_id, ON DELETE SET NULL)

agentes (id, nome, jogador, classe, nex, foto_arquivo, atributos×5,
         barras×3, defesa, narrativa×4, ...)
   ├── 1:N → agente_pericias    (UNIQUE agente_id+pericia)
   ├── 1:N → agente_ataques     (ON DELETE CASCADE)
   ├── 1:N → agente_inventario  (ON DELETE CASCADE)
   └── 1:N → agente_rituais     (ON DELETE CASCADE)

log_rolagens (id, quem_rolou, descricao, tipo_dado ENUM(d4..d100),
              quantidade_dados, resultados_brutos JSON, resultado_final,
              eh_critico, eh_desastre, rolado_em)
```

## 10. Repositório GitHub

- **URL**: https://github.com/arturmuller25/clarividenciarpg
- **Branch padrão**: `main`
- **Como publicar mudanças**:
  1. Editar arquivos em `c:\Users\Usuario\Downloads\clarividencia rpg\`
  2. Copiar para o clone local em `$env:TEMP\clarividenciarpg-deploy\`
     (ou onde estiver — pode mover para `c:\Users\Usuario\repos\clarividenciarpg\` para uso permanente)
  3. `git add -A && git commit -m "msg" && git push`
  4. Git Credential Manager autentica via OAuth no navegador (1ª vez).

Git instalado em `C:\Program Files\Git\cmd\git.exe` (versão 2.54).

## 11. Onde olhar para entender X

| Quero entender... | Olhe primeiro... |
|---|---|
| Como a hero d20 funciona | `index.php` (markup), `assets/css/terminal.css` (regras `.hero__*`), `assets/js/hero.js` (geometria 3D + render loop) |
| Como o icosaedro 3D é renderizado | `assets/js/hero.js`. Geometria via razão áurea (PHI), 12 vértices, 20 faces, painter's algorithm + back-face culling. Procurar `VERTS_RAW`, `FACES`, `RESTING`, `renderPose` |
| Por que tem botão "// INICIAR" antes da animação | Decisão 029. Política de autoplay do browser bloqueia áudio até gesto humano; click pré-animação libera autoplay para a sessão e garante sincronia frame-a-frame da queda |
| Como funciona o gate de autoplay da Hero | `assets/js/hero.js` (procurar `dispararAnimacao`, `clicouIniciar`, `iniciarEspera`, `_modoEspera`). Decisão 029 explica o porquê |
| Por que a Hero não roda em todo navegação | Decisão 019. `hero.js::deveRodarHero()` usa Performance Navigation API + sessionStorage |
| Como a Hero trava o scroll do body | Decisão 030. Classe `body.hero-ativa` adicionada/removida pelo `hero.js`; CSS `body.hero-ativa { overflow: hidden }`. Esc + wheel-down como acionadores alternativos do "// ROMPER O VÉU" após 5.8s |
| Como o áudio da Hero é tocado | `assets/js/hero.js`. `audio.load()` no DOMContentLoaded, `await audio.play()` antes do RAF inicial. Loop ambiente em t=3.7s com fadeIn 1s. fadeOutLoopEFechar 800ms ao clicar "// ROMPER O VÉU" (ou Esc/scroll-down após 5.8s) |
| Como o áudio das rolagens é tocado | `assets/js/dados.js` (som em camadas) |
| Por que sons da rolagem soam balanceados | Decisão 021. Calibragem manual via `VOLUMES.*` no topo do `dados.js` (não reencodamos os MP3) |
| Por que multi-dado d20 e d6 funcionam diferente | Decisão 020. d20 mantém regra OP (vantagem); demais somam todos os valores |
| Por que tem 3 atributos `hidden`/`display`/`is-oculta` no SVG | Decisão 011. Resumo: `hidden` em `<g>` SVG é unreliable em alguns browsers |
| Onde fica o sprite dos dados | `assets/img/dice-icons.svg` (não mais inline em `rolagem/index.php`). IDs `geo-dN` preservados ao copiar do design system — Decisão 026. Incluído via `<?php readfile() ?>` |
| Onde fica o sprite dos 5 elementos | `assets/img/elementos-icons.svg`. IDs `el-sangue/-morte/-conhecimento/-energia/-medo`. Incluído globalmente via `readfile()` em `views/cabecalho.php`. Decisão 024 (sem FontAwesome) |
| Por que a paleta é "envenenada" (mostarda em vez de neon dourado) | Decisão 025. Refit direto no `:root` durante a refatoração visual (Phase 6, Passo 5) |
| Quais tokens estão disponíveis no `:root` (spacing, type, ls, shadow, animation) | Decisão 027. Adicionados como acréscimo durante Phase 6, Passo 5 |
| Por que Cormorant Garamond e IM Fell English aparecem no Painel | Decisão 028. Escopo restrito a "Sussurros do Outro Lado" e citações — não promovidas a `--corpo`/`--titulo` |
| Como o cropper 1:1 funciona | `assets/js/cropper.js`. Decisão 016: vanilla canvas, sem libs externas |
| Por que `<FilesMatch>` em `/uploads/.htaccess` está em 3 linhas | Decisão 017. Apache 2.4 não tolera abrir e fechar na mesma linha |
| Como CSRF funciona | `src/sessao.php` |
| Como upload é seguro | `src/UploadHelper.php` + `uploads/.htaccess` |
| Como salvamento da ficha é transacional | `src/AgenteRepositorio.php::criar()` e `atualizar()` |
| Quais decisões arquiteturais foram tomadas | `decisoes.md` (atual: ADR 030) |
| O que aconteceu em cada sessão | `logs.md` (atual: Sprint 23) |
| Como foi feita a refatoração visual via Claude Design | `INTEGRACAO_DESIGN.md` (mantido como artefato histórico do processo) |

## 12. O que NÃO está documentado aqui (mas deveria, em algum momento)

- Diagrama ER do banco em formato visual (PlantUML ou dbdiagram.io).
- Roteiro de testes manuais para cada módulo.
- Cronograma de entregas para o trabalho acadêmico.

Se for relevante para você, adicione e me peça para integrar.
