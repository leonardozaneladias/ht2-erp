# Template Inspinia × Componentes Blade — Índice

> **Documento índice.** Ponto de entrada para a documentação do template Inspinia v5.0 Tailwind
> e do catálogo de componentes Blade derivados dele.
>
> **Leia este documento primeiro** quando precisar saber por onde começar.

**Versão:** 3.0.0
**Data:** 2026-04-11

---

## 1. Navegação rápida

| Documento                                                | Quando abrir                                                                                                                                                          |
| -------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 📇 [`CATALOGO-COMPONENTES.md`](CATALOGO-COMPONENTES.md)  | **Precisa saber se um componente existe.** Fonte de verdade dos componentes Blade, organizados por categoria, com status, prioridade, decisão arquitetural e link para a doc técnica. |
| 📦 [`README.md`](README.md)                              | **Quer ver o inventário.** Lista os `.blade.php` do template Inspinia original, a skin/cores recomendadas, versões de plugins e validação contra a spec.              |
| 📄 `<Categoria>/<nome>.md`                               | **Precisa da doc técnica** de um componente específico (código, props, dependências, uso). Cada componente do catálogo tem um arquivo `.md` na pasta da sua categoria. |
| 🧭 [`../../../CLAUDE.md`](../../../CLAUDE.md) §9          | **Regras de código** dos componentes Blade (onde ficam, como nomear, como propagar erros).                                                                            |

---

## 2. Fluxo de trabalho

```
┌──────────────────────────────────────────────────────────────────────┐
│  Vou iniciar uma tela/CRUD do admin                                   │
│  └─> CATALOGO-COMPONENTES.md — identificar os componentes necessários │
│      └─> para cada um, abrir a doc em <Categoria>/<nome>.md           │
├──────────────────────────────────────────────────────────────────────┤
│  Vou criar um componente novo                                         │
│  └─> 1) verificar se já existe em CATALOGO-COMPONENTES.md             │
│      2) se não existe, criar doc em <Categoria>/<nome>.md            │
│      3) catalogar em CATALOGO-COMPONENTES.md                         │
│      4) criar o .blade.php em resources/views/components/            │
├──────────────────────────────────────────────────────────────────────┤
│  Preciso investigar "existe algo no Inspinia para X?"                │
│  └─> README.md (inventário completo do template original)            │
├──────────────────────────────────────────────────────────────────────┤
│  Estou debugando um componente                                       │
│  └─> <Categoria>/<nome>.md (props, dependências)                     │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 3. Estrutura de pastas

```
docs/template/INSPINIA/
├── template-map.md              ← você está aqui (índice)
├── CATALOGO-COMPONENTES.md       ← catálogo de componentes (fonte de verdade)
├── README.md                     ← inventário do template Inspinia v5.0
│
├── Layouts/                      ← layouts base
├── Components/
│   ├── Navigation/               ← sidebar, topbar, footer, page-header
│   ├── UI/                       ← card, tabs, modal, alert, etc.
│   ├── Data/                     ← kpi-card, status-badge, etc.
│   └── Feedback/                 ← toast, empty-state, confirm-dialog, etc.
├── Forms/                        ← inputs, selects, datepickers, máscaras, upload
├── Tables/                       ← static-table, custom-table, data-table
├── Charts/                       ← wrappers ApexCharts (bar, line, column, pie)
├── Dashboards/                   ← views de dashboard de referência
├── Pages/                        ← páginas (auth, erros, utility)
└── Plugins/                      ← sortable, clipboard, pass-meter, etc.
```

A documentação técnica de cada componente fica no arquivo `.md` da sua categoria.
O Blade real correspondente fica em `resources/views/components/` (namespaces `x-admin.*` e `x-shared.*`).

---

## 4. Princípios

1. **Antes** de escrever HTML, consultar o [`CATALOGO-COMPONENTES.md`](CATALOGO-COMPONENTES.md).
2. O template Inspinia é matéria-prima; a fonte de verdade da UI final é o catálogo Blade.
3. Ordem de preferência para nova UI: reuso → composição → variação por props → componente novo.
4. Páginas completas **não** são componentes.

---

## Changelog

| Data       | Versão | Descrição                                                                          |
| ---------- | :----: | ---------------------------------------------------------------------------------- |
| 2026-04-11 | 3.0.0  | Índice reescrito como entrada enxuta: catálogo, inventário e pastas de componentes |
