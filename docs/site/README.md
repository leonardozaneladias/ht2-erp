---
title: Sobre a publicação da documentação
version: 1.0.0
date: 2026-04-18
status: draft
---

# Site de Documentação — Docsify

Este diretório (`docs/site/`) contém apenas artefatos auxiliares da publicação.
A raiz da documentação servida é o diretório `docs/` acima.

## Estrutura

```
docs/
├── index.html           ← entry-point do Docsify
├── README.md            ← homepage (home/landing)
├── _sidebar.md          ← navegação lateral
├── _navbar.md           ← navegação topo
├── _coverpage.md        ← cover page
├── .nojekyll            ← impede Jekyll no GitHub Pages
├── site/
│   ├── custom.css       ← estilo customizado
│   └── README.md        ← este arquivo
├── product/
├── architecture/
│   └── adrs/
├── data/
├── api/
├── qa/
├── devops/
├── prd/
└── … (guias existentes numerados)
```

## Como servir localmente

### 1. Via docsify-cli (recomendado)

```bash
npm install -g docsify-cli
docsify serve docs --port 3000 --open
```

### 2. Via npx (sem instalar global)

```bash
npx docsify-cli serve docs --port 3000
```

### 3. Via Python (sem plugins Docsify)

```bash
python3 -m http.server 3000 --directory docs
```

Abra `http://localhost:3000`.

## Plugins habilitados (via CDN em `index.html`)

| Plugin                                                | Função                                                |
| ----------------------------------------------------- | ----------------------------------------------------- |
| `docsify` 4                                           | Core do renderizador                                  |
| `docsify-sidebar-collapse`                            | Sidebar com colapso por grupo                         |
| `docsify/plugins/search.min.js`                       | Busca full-text                                       |
| `docsify-copy-code`                                   | Botão "Copiar" em blocos de código                    |
| `docsify-pagination`                                  | Paginação inferior (próximo/anterior)                 |
| `docsify-tabs`                                        | Suporte a `<!-- tabs:start -->` / `<!-- tabs:end -->` |
| `prismjs` (bash, php, sql, yaml, json, nginx, docker) | Syntax highlighting                                   |
| `mermaid` 10                                          | Diagramas em blocos ` ```mermaid `                    |

## Como adicionar um novo documento

1. Escreva o `.md` no diretório apropriado (ex.: `docs/architecture/algum-novo-doc.md`).
2. Adicione entrada em `docs/_sidebar.md` na seção correta.
3. Atualize `docs/_navbar.md` se for um link de navegação principal.
4. Inclua o frontmatter YAML padrão:
    ```yaml
    ---
    title: Título
    version: 1.0.0
    date: AAAA-MM-DD
    status: draft|proposed|accepted|deprecated
    ---
    ```
5. Rode `docsify serve docs` e verifique a renderização, com atenção a:
    - Diagramas Mermaid no tema padrão
    - Links relativos (usar `../` quando cruzar diretórios)
    - Tabelas com alinhamento correto
    - Blocos `yaml`, `php`, `sql`, `nginx` com syntax highlight

## Publicação

### GitHub Pages

1. Ativar Pages em _Settings → Pages → Branch: main, folder: `/docs`_.
2. O arquivo `.nojekyll` presente garante que `_files` não sejam tratados como Jekyll.
3. O site ficará em `https://<org>.github.io/<repo>/`.

### Netlify / Vercel / Cloudflare Pages

- **Build command:** (vazio, estático)
- **Publish directory:** `docs`
- **Index file:** `docs/index.html`

### Futuro — Backstage TechDocs

A estrutura de pastas `docs/` + `docs/README.md` + frontmatter YAML já é compatível com **MkDocs** e **Backstage TechDocs**.

Para migrar:

1. Criar `mkdocs.yml` na raiz do repositório apontando para `docs_dir: docs`.
2. Publicar pipeline TechDocs via `techdocs-cli generate` em CI.
3. `_sidebar.md` → `mkdocs.yml:nav:` (equivalência direta).
4. `_coverpage.md` → tratar como landing `index.md` com MkDocs Material.
5. Mermaid → habilitar extensão `pymdownx.superfences` com custom fence.

## Convenções

- **Um arquivo = um tópico.** Se passar de ~2000 linhas, considerar split.
- **Links relativos** sempre (ex.: `[SAD](../architecture/SAD-arc42.md)`). Nunca absolutos.
- **Imagens** em `site/assets/` com nome `kebab-case.svg|png`. SVG preferido.
- **Blocos Mermaid** devem ter `graph TD` ou similar explícito. Evite `flowchart LR` sem direção.
- **Tabelas grandes** de referência → considerar dividir por bounded context.
