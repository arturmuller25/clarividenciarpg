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
│   ├── css/terminal.css   ← folha única (~2200 linhas, organizada por módulo)
│   ├── js/
│   │   ├── validacao.js   ← validação de form + confirmações + contadores
│   │   ├── dados.js       ← rolagem multi-dado + áudio
│   │   ├── hero.js        ← splash screen com d20 3D + ondas de choque
│   │   └── agente.js      ← ficha dinâmica (barras, ataques, listas)
│   └── audio/
│       ├── som_para_a_hero.mp3
│       └── som_para_as_rolagens.mp3
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
| **5** | Pendente. Veja "Backlog" abaixo. | ⏳ |

## 5. Backlog (próximas sessões)

Ordem sugerida:

1. **AJAX para barras de PV/SAN/PE** — endpoint que persiste mudança de uma coluna isoladamente, sem ter que dar submit no formulário inteiro. Útil durante combate.
2. **Página de visualização da Campanha** com gestão bidirecional de vínculos (assign/remove agentes/NPCs/criaturas pelo lado da campanha).
3. **Upload de foto para NPCs e Criaturas** — schema já suporta (`foto_arquivo`), só falta input + handler nos formulários.
4. **Cálculo automático de bônus de perícia** conforme regras canônicas: Treinado = +metade do NEX, Veterano = +metade do NEX + 2, Especialista = +metade do NEX + 4. Hoje o usuário escreve `bonus_extra` à mão.
5. **Sistema de iniciativa em combate** (mencionado no brief original, ainda não atacado).
6. **Endpoint de rolagem de perícia/ataque** que use os valores da ficha ao invés do form de rolagem genérico.
7. **Importar/Exportar ficha como JSON** (para salvar/recuperar entre campanhas).
8. **Modo público de campanha** (URL com slug que jogadores podem visitar para ver as próprias fichas).

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
| Como a hero d20 funciona | `index.php` (HTML), `assets/css/terminal.css` (procurar `hero-rolar-3d`), `assets/js/hero.js` |
| Como o áudio é tocado | `assets/js/hero.js` (autoplay + fallback), `assets/js/dados.js` (som da rolagem) |
| Por que tem 3 atributos `hidden`/`display`/`is-oculta` no SVG | Decisão 008+ em `decisoes.md`. Resumo: `hidden` em `<g>` SVG é unreliable. |
| Como CSRF funciona | `src/sessao.php` |
| Como upload é seguro | `src/UploadHelper.php` + `uploads/.htaccess` |
| Como salvamento da ficha é transacional | `src/AgenteRepositorio.php::criar()` e `atualizar()` |
| Quais decisões arquiteturais foram tomadas | `decisoes.md` |
| O que aconteceu em cada sessão | `logs.md` |

## 12. O que NÃO está documentado aqui (mas deveria, em algum momento)

- Diagrama ER do banco em formato visual (PlantUML ou dbdiagram.io).
- Roteiro de testes manuais para cada módulo.
- Cronograma de entregas para o trabalho acadêmico.

Se for relevante para você, adicione e me peça para integrar.
