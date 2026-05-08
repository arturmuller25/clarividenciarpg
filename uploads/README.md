# /uploads — Imagens enviadas pelo Mestre

Estrutura:

```
uploads/
├── .htaccess        ← bloqueia execução PHP/CGI nesta árvore
├── campanhas/       ← capas de campanha
├── agentes/         ← fotos de agentes (PJs)
├── npcs/            ← fotos de NPCs
└── criaturas/       ← fotos de criaturas do bestiário
```

Os nomes de arquivo são gerados pelo PHP (não são confiáveis vindos do
cliente) e armazenados no banco em colunas `*_arquivo VARCHAR(160)`.

Limites recomendados (a aplicar no PHP do upload — fase 2):

- **Tamanho máximo**: 4 MB por imagem.
- **Formatos aceitos**: JPEG, PNG, WebP. Nada de SVG (XSS) ou GIF animado.
- **MIME validado** com `finfo_file()`, NÃO com `$_FILES['x']['type']`.
- **Nome final**: `bin2hex(random_bytes(8)) . '.' . $extSegura`.
