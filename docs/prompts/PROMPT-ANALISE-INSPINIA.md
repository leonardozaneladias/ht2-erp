# Prompt — Análise, Documentação e Componentização do Template Inspinia

Cole este prompt no Claude Code dentro do projeto Portal ArtFinal.

---

IDIOMA: Toda documentação em Português PT-BR. Nomes de classes, componentes Blade e código em inglês.

## CONTEXTO

Leia estes arquivos antes de começar:

- `CLAUDE.md` (raiz do projeto)
- `.docs/ARCHITECTURE-GUIDE.md` (padrões de componentes, API-Ready)
- `.docs/CONVENTIONS.md` (padrões de código)
- `.docs/PRD_v3.1.0.md` seção 14 (todas as 20+ telas do admin) e seção 19 (stack)
- `.docs/INSPINIA-ANALISE.md` (análise prévia do template)

**Template Inspinia (Tailwind + Laravel):**
`~/Projects/Templates/Laravel/INSPINIA_v5.0/Tailwind CSS/Laravel/inspinia`

**Documentação oficial do template:**
`/Users/leonardozaneladias/Projects/Templates/Laravel/INSPINIA_v5.0/Tailwind CSS/Laravel/Docs`

Leia TAMBÉM a documentação do próprio Inspinia em `Docs/` — ela contém informações oficiais sobre props, configuração e uso que devem ser incorporadas na nossa documentação.

---

## SPECS DO TEMPLATE (referência para validação)

Ao percorrer o template, valide que TODOS estes itens foram encontrados e documentados. Marque ✅ ou ❌ para cada um:

### Features Gerais

- [ ] Built using Bootstrap (v5.3.8) — verificar se a versão Tailwind ainda traz resquícios
- [ ] Powered by Tailwind CSS (v4.2.x) — confirmar versão exata
- [ ] Responsive Layout (desktops, tablets, mobile)
- [ ] Light & Dark Theme Modes (CSS Based)
- [ ] 6 Skins: Classic (Default), SaaS, Modern, Flat, Material, Minimal (CSS Based)
- [ ] SCSS Variables para customização
- [ ] Vertical & Horizontal Layouts
- [ ] 235+ Pages — contar e confirmar
- [ ] 15+ Apps — listar todas
- [ ] 4 Types of Topbar Support — documentar cada um
- [ ] 6 Types of Sidenav Sizes: Default, Compact, Condensed, On Hover, On Hover Show, Offcanvas
- [ ] 5 Types of Sidenav Colors: Light, Gray, Dark, Gradient, Image
- [ ] Fixed & Scrollable Layouts
- [ ] Sidebar with User Support
- [ ] TanStack Tables Support (React, Angular, NextJS) — verificar se tem na versão Laravel
- [ ] Widgets & Metrics Pages
- [ ] Fluid & Boxed View
- [ ] Authentication & Error Pages (3 Different Styles)
- [ ] Font-Based Icons (Tabler & Lucide)
- [ ] Google Fonts
- [ ] HTML5 & CSS3
- [ ] W3C Validated Code
- [ ] 3 Unique Dashboards
- [ ] Landing Page Included
- [ ] i18n Support

### Apps (15+ — documentar cada um)

- [ ] Email App
- [ ] E-commerce Pages (Marketplace, Products, Products Grid, Product Details, Create Products, Cart, Checkout, Refunds, Reports, Orders, Order Details, Customers, Sellers, Review)
- [ ] Users (Contacts, Roles, Permissions)
- [ ] Projects (Project list & Grid View, Detail Page, Team Board, Activity Stream & Kanban Board)
- [ ] File Manager
- [ ] Chat
- [ ] Calendar
- [ ] Social Feed
- [ ] Invoice Management (Invoice List, Details, Add New Invoice)
- [ ] Outlook View
- [ ] Companies List
- [ ] Clients
- [ ] Vote List
- [ ] Issue Tracker
- [ ] API Keys
- [ ] Blog Management (Grid/List Views, Details Page, Add Blog)
- [ ] Pin Board
- [ ] Forum View and Detail Page
- [ ] PDF Viewer
- [ ] Idle Timer
- [ ] Live Favicon
- [ ] Text Diff

### Components (documentar cada um)

- [ ] All Bootstrap Components (verificar quais vieram na versão Tailwind)
- [ ] Loading buttons
- [ ] Text Editors (Quilljs & Summernote)
- [ ] Form Basic Components
- [ ] Form Validations
- [ ] Form Wizard (Custom)
- [ ] Pickers (Date Range Picker, Flatpickr, Colorpicker)
- [ ] Select (Choices.js, Tagify, Select2)
- [ ] File Upload (Dropzone & FilePond)
- [ ] noUiSlider Range Slider
- [ ] Inputmask
- [ ] Typeahead
- [ ] Input Touchspin (Custom)
- [ ] Bootstrap Carousel
- [ ] Bootstrap Tables
- [ ] Custom Tables (Exclusive in INSPINIA)
- [ ] 15+ Datatables (Basic, Export, Select, Ajax, JS Source, Data Rendering, Scroll, Fixed Columns, Fixed Header, Show & Hide Column, Child Rows, Column Searching, Range Search, Add Rows, Checkbox Select)
- [ ] Tabler & Lucide Icons
- [ ] All Countries SVG-Based Flags
- [ ] Google, Vector, and Leaflet Maps

### Pages (documentar cada uma)

- [ ] Profile Page
- [ ] FAQ
- [ ] Pricing
- [ ] Timeline
- [ ] Search Results
- [ ] Starter Page (Empty Page)
- [ ] Coming Soon Page
- [ ] Terms & Conditions
- [ ] Sortable List
- [ ] SweetAlerts 2
- [ ] Password Meter
- [ ] Clipboard
- [ ] Tree View
- [ ] Gallery
- [ ] Masonry Support
- [ ] Tour Page
- [ ] Animate CSS Support

### Auth & Error Pages (3 estilos: Basic, Card, Split)

- [ ] Sign In (3 estilos)
- [ ] Sign Up
- [ ] Reset Password
- [ ] New Password
- [ ] Two Factor
- [ ] Lock Screen
- [ ] Success Mail
- [ ] Login with PIN
- [ ] Delete Account
- [ ] Error 400, 401, 403, 404, 408, 500
- [ ] Maintenance Page

### Charts

- [ ] ApexCharts: Area, Bar, Bubble, Candlestick, Column, Heatmap, Line, Mixed, Timeline, Boxplot, Treemap, Pie, Radar, RadialBar, Scatter, Polar Area, Sparklines, Range, Funnel, Slope
- [ ] ECharts: Line, Bar, Pie, Scatter, GEO Map, Gauge, Candlestick, Area, Radar, Heatmap

Ao final da Fase 1, reportar esta checklist preenchida com ✅/❌ e observações para cada item.

---

## OBJETIVO

Criar uma **documentação granular e autocontida** de CADA componente, plugin, página e recurso do Inspinia. Cada item tem seu próprio arquivo `.md` com instruções detalhadas, código original, componente Blade proposto, exemplos reais do domínio (formandos, contratos, parcelas), casos de uso, anti-patterns e mapeamento com o PRD.

Os arquivos de catálogo funcionam apenas como **índice com links** para a documentação detalhada.

Quando o dev (ou a IA) precisar usar um componente, abre o `.md` específico e tem tudo: HTML original, componente Blade proposto com props/slots, exemplos práticos, e sabe exatamente onde e quando usar.

---

## ESTRUTURA DE PASTAS

Criar em `.docs/template/INSPINIA/`:

```
.docs/template/INSPINIA/
│
├── README.md                          ← Índice mestre com links para tudo
│
├── Layouts/
│   ├── vertical.md
│   ├── horizontal.md
│   ├── boxed.md
│   ├── fluid.md
│   └── skins.md                       ← Default, SaaS, Modern, Flat, Material, Minimal
│
├── Components/
│   ├── UI/
│   │   ├── accordion.md
│   │   ├── alert.md
│   │   ├── badge.md
│   │   ├── breadcrumb.md
│   │   ├── button.md
│   │   ├── card.md
│   │   ├── carousel.md
│   │   ├── collapse.md
│   │   ├── dropdown.md
│   │   ├── list-group.md
│   │   ├── modal.md
│   │   ├── notification.md
│   │   ├── offcanvas.md
│   │   ├── pagination.md
│   │   ├── popover.md
│   │   ├── progress.md
│   │   ├── spinner.md
│   │   ├── tab.md
│   │   ├── tooltip.md
│   │   └── typography.md
│   ├── Data/
│   │   ├── kpi-card.md
│   │   ├── widget.md
│   │   ├── metric.md
│   │   └── status-badge.md
│   ├── Navigation/
│   │   ├── sidebar.md
│   │   ├── topbar.md
│   │   ├── footer.md
│   │   └── mega-menu.md
│   └── Feedback/
│       ├── toast.md
│       ├── sweetalert.md
│       ├── loading-button.md
│       ├── empty-state.md
│       └── confirm-dialog.md
│
├── Forms/
│   ├── input.md
│   ├── textarea.md
│   ├── select-native.md
│   ├── select-choices.md
│   ├── select-tagify.md
│   ├── select-select2.md
│   ├── datepicker-flatpickr.md
│   ├── daterange-picker.md
│   ├── colorpicker.md
│   ├── file-upload-dropzone.md
│   ├── file-upload-filepond.md
│   ├── input-mask.md
│   ├── range-slider.md
│   ├── toggle-switch.md
│   ├── checkbox-radio.md
│   ├── password-meter.md
│   ├── touchspin.md
│   ├── typeahead.md
│   ├── wizard.md
│   ├── validation.md
│   └── text-editor-quill.md
│
├── Tables/
│   ├── static-table.md
│   ├── custom-table.md
│   ├── datatable-basic.md
│   ├── datatable-export.md
│   ├── datatable-select.md
│   ├── datatable-ajax.md
│   ├── datatable-scroll.md
│   ├── datatable-fixed-columns.md
│   ├── datatable-fixed-header.md
│   ├── datatable-column-search.md
│   ├── datatable-range-search.md
│   ├── datatable-child-rows.md
│   ├── datatable-checkbox.md
│   ├── datatable-add-rows.md
│   └── datatable-show-hide-columns.md
│
├── Charts/
│   ├── ApexCharts/
│   │   ├── area.md
│   │   ├── bar.md
│   │   ├── bubble.md
│   │   ├── candlestick.md
│   │   ├── column.md
│   │   ├── heatmap.md
│   │   ├── line.md
│   │   ├── mixed.md
│   │   ├── pie.md
│   │   ├── radar.md
│   │   ├── radialbar.md
│   │   ├── scatter.md
│   │   ├── sparklines.md
│   │   ├── timeline.md
│   │   ├── boxplot.md
│   │   ├── treemap.md
│   │   ├── polar-area.md
│   │   ├── range.md
│   │   ├── funnel.md
│   │   └── slope.md
│   └── ECharts/
│       ├── line.md
│       ├── bar.md
│       ├── pie.md
│       ├── scatter.md
│       ├── geo-map.md
│       ├── gauge.md
│       ├── candlestick.md
│       ├── area.md
│       ├── radar.md
│       └── heatmap.md
│
├── Icons/
│   ├── tabler.md
│   ├── lucide.md
│   └── flags.md
│
├── Maps/
│   ├── google-maps.md
│   ├── vector-maps.md
│   └── leaflet-maps.md
│
├── Pages/
│   ├── Auth/
│   │   ├── sign-in-basic.md
│   │   ├── sign-in-card.md
│   │   ├── sign-in-split.md
│   │   ├── sign-up.md
│   │   ├── reset-password.md
│   │   ├── new-password.md
│   │   ├── two-factor.md
│   │   ├── lock-screen.md
│   │   ├── success-mail.md
│   │   ├── login-pin.md
│   │   └── delete-account.md
│   ├── Error/
│   │   ├── 400.md
│   │   ├── 401.md
│   │   ├── 403.md
│   │   ├── 404.md
│   │   ├── 408.md
│   │   ├── 500.md
│   │   └── maintenance.md
│   ├── Utility/
│   │   ├── profile.md
│   │   ├── account-settings.md
│   │   ├── faq.md
│   │   ├── pricing.md
│   │   ├── timeline.md
│   │   ├── search-results.md
│   │   ├── coming-soon.md
│   │   ├── terms-conditions.md
│   │   ├── gallery.md
│   │   ├── sitemap.md
│   │   └── empty-page.md
│   └── Apps/
│       ├── email.md
│       ├── ecommerce-marketplace.md
│       ├── ecommerce-products.md
│       ├── ecommerce-product-detail.md
│       ├── ecommerce-cart.md
│       ├── ecommerce-checkout.md
│       ├── ecommerce-orders.md
│       ├── ecommerce-customers.md
│       ├── users-contacts.md
│       ├── users-roles.md
│       ├── users-permissions.md
│       ├── projects-list.md
│       ├── projects-kanban.md
│       ├── file-manager.md
│       ├── chat.md
│       ├── calendar.md
│       ├── social-feed.md
│       ├── invoice-list.md
│       ├── invoice-detail.md
│       ├── invoice-create.md
│       ├── companies-list.md
│       ├── clients.md
│       ├── blog-grid.md
│       ├── blog-detail.md
│       ├── blog-create.md
│       ├── vote-list.md
│       ├── issue-tracker.md
│       ├── api-keys.md
│       ├── pin-board.md
│       ├── forum.md
│       ├── pdf-viewer.md
│       └── outlook-view.md
│
├── Plugins/
│   ├── sortable-list.md
│   ├── clipboard.md
│   ├── tree-view.md
│   ├── tour.md
│   ├── animation.md
│   ├── masonry.md
│   ├── video-player.md
│   ├── idle-timer.md
│   ├── live-favicon.md
│   └── text-diff.md
│
└── Dashboards/
    ├── default.md
    ├── saas.md
    └── analytics.md
```

---

## TEMPLATE PARA CADA ARQUIVO .md

CADA arquivo de documentação DEVE seguir este template completo:

````markdown
# [Nome do Componente/Página/Plugin]

**Categoria:** [UI Component | Form | Table | Chart | Page | Plugin | Layout | Dashboard]
**Origem Inspinia:** `[caminho relativo do arquivo no template]`
**Plugins JS:** [nome + versão ou "Nenhum"]
**Plugins CSS:** [nome ou "Apenas Tailwind"]
**Documentação Inspinia:** `[caminho em Docs/ se existir ou "Não disponível"]`

---

## Descrição

[O que é, para que serve, comportamento principal — 2-3 frases claras]

---

## Preview Visual

[Descrição detalhada de como o componente aparece visualmente:

- Estrutura (o que fica onde)
- Elementos visuais (ícones, cores, bordas, sombras)
- Estados (hover, active, disabled, loading)
- Variantes disponíveis (tamanhos, cores, estilos)
- Comportamento responsivo (como muda em mobile)
- Dark mode (como muda)]

---

## Código Original (Inspinia)

```html
[Copiar o HTML essencial do template Inspinia. - Remover o wrapper de layout (apenas o componente em si) - Manter todas
as classes Tailwind - Manter atributos de data/interação - Limpar conteúdo demo mas manter a estrutura - Se o componente
tem variantes, mostrar as mais relevantes]
```
````

[Se usar JavaScript/Alpine.js, incluir também:]

```javascript
[Código JS relevante do componente]
```

---

## Componente Blade Proposto

**Nome:** `<x-[admin|shared].[nome-kebab]>`
**Arquivo view:** `resources/views/components/[admin|shared]/[nome-kebab].blade.php`
**Classe PHP:** `app/View/Components/[Admin|Shared]/[NomePascal].php` (ou "Blade anônimo — sem classe")
**Tipo:** Blade anônimo | Class-based component | Livewire component

### Props

| Prop | Tipo | Obrigatório | Default | Descrição |
| ---- | ---- | :---------: | ------- | --------- |
|      |      |             |         |           |

### Slots

| Slot              | Descrição                   |
| ----------------- | --------------------------- |
| `$slot` (default) | [conteúdo principal]        |
| `[nomeSlot]`      | [conteúdo opcional nomeado] |

### Código do Componente Blade

```php
{{-- resources/views/components/[area]/[nome].blade.php --}}
@props([
    // ... props com defaults
])

[HTML do componente adaptado do Inspinia, com:
- @props no topo
- {{ $slot }} para conteúdo dinâmico
- Suporte a dark mode (dark: classes)
- Classes condicionais com @class
- Wire:model se Livewire]
```

---

## Exemplos de Uso

### Exemplo Básico

```html
[Exemplo mínimo funcional — o mais simples possível]
```

### Exemplo Real (Portal ArtFinal)

```html
[Exemplo usando dados reais do domínio do projeto: - Formandos, contratos, parcelas, instituições - Enums do projeto
(StatusParcela, ModalidadePagamento) - MoneyHelper para valores - Integração com Livewire se aplicável]
```

### Exemplo com Variantes

```html
[Mostrar 2-3 variações de uso com props diferentes]
```

### Exemplo com Livewire (se aplicável)

```php
[Mostrar integração com componente Livewire:
- wire:model para binding
- wire:click para ações
- wire:loading para estados de loading]
```

---

## Quando Usar ✅

- [Cenário 1 — descrição clara de quando este componente é a escolha ideal]
- [Cenário 2]
- [Cenário 3]
- [Cenário 4 — se aplicável]

## Quando NÃO Usar ❌

- [Cenário 1 — e qual componente usar no lugar]
- [Cenário 2 — e por quê]
- [Anti-pattern: o que parece ser bom uso mas não é]

## Boas Práticas 💡

- [Dica 1 de uso eficiente]
- [Dica 2 — acessibilidade, performance, UX]
- [Dica 3 — integração com outros componentes]

---

## Mapeamento no PRD (Portal ArtFinal)

| Tela   | Seção PRD | Como É Usado           | Sprint |
| ------ | --------- | ---------------------- | :----: |
| [Nome] | 14.X      | [Descrição específica] |   XX   |
| [Nome] | 14.X      | [Descrição específica] |   XX   |

---

## Classificação

| Critério                   | Valor                                              |
| -------------------------- | -------------------------------------------------- |
| **Vai usar no projeto**    | 🟢 Sim / 🟡 Possivelmente / 🔴 Não                 |
| **Prioridade**             | P0 (Sprint 1) / P1 (4-9) / P2 (15-19) / P3 (20-24) |
| **Sprint planejada**       | [número ou "a definir"]                            |
| **Complexidade**           | Simples / Média / Complexa                         |
| **Status componentização** | 🔴 Não iniciado / 🟡 Em progresso / 🟢 Pronto      |

---

## Dependências

| Tipo                         | Item                                            |
| ---------------------------- | ----------------------------------------------- |
| **Depende de (JS)**          | [plugins JS necessários]                        |
| **Depende de (componentes)** | [outros componentes Blade que usa internamente] |
| **Usado por (telas)**        | [telas do admin que consomem este]              |
| **Usado por (componentes)**  | [componentes maiores que incluem este]          |

---

## Notas de Adaptação

[O que precisa mudar do original Inspinia para o projeto:

- Remoção de classes Bootstrap (se houver mistura)
- Adaptação para dark mode Tailwind
- Integração com Alpine.js ou Livewire
- Ajustes de responsividade
- Customização de cores/tema
- Troca de ícones (se necessário)
- Otimização de performance]

---

## Changelog do Componente

| Data           | Descrição           |
| -------------- | ------------------- |
| [data criação] | Documentação criada |

````

---

## EXECUÇÃO

### FASE 1 — Inventário, Leitura e Validação de Specs (reportar antes de continuar)

1. Percorra TODA a estrutura de arquivos do template Inspinia em:
   `~/Projects/Templates/Laravel/INSPINIA_v5.0/Tailwind CSS/Laravel/inspinia`

2. Leia a documentação oficial do Inspinia em:
   `/Users/leonardozaneladias/Projects/Templates/Laravel/INSPINIA_v5.0/Tailwind CSS/Laravel/Docs`

3. **Preencha a checklist de SPECS** (seção acima) marcando ✅/❌ para cada item.
   Para cada item ❌ (não encontrado), explicar: "não existe na versão Tailwind" ou "existe com nome diferente: [nome]"

4. Contar e confirmar:
   - Total de páginas encontradas (specs dizem 235+)
   - Total de apps encontradas (specs dizem 15+)
   - Total de DataTables (specs dizem 15+)
   - Total de dashboards (specs dizem 3)
   - Total de estilos de auth (specs dizem 3)
   - Total de skins (specs dizem 6)
   - Versão exata do Tailwind CSS
   - Plugins JS com versões exatas

5. Crie `.docs/template/INSPINIA/README.md` com:
   - Visão geral, versões detectadas, sistema de build
   - Skin escolhida (SaaS ou Default)
   - Índice completo com links para todos os arquivos de documentação
   - Total de itens por categoria

**Reporte o resultado da Fase 1 antes de prosseguir.**

### FASE 2 — Documentação Granular (por prioridade)

Criar os arquivos `.md` seguindo o template definido acima. Para CADA item:

1. Criar o arquivo na hierarquia `.docs/template/INSPINIA/[Categoria]/[nome].md`
2. Preencher TODOS os campos do template (nenhum campo vazio)
3. Copiar código HTML do Inspinia (limpo, sem wrapper de layout)
4. Cruzar com a documentação em `Docs/` e incorporar informações oficiais
5. Propor o componente Blade completo com código pronto para copiar
6. Exemplos REAIS usando dados do domínio (formandos, parcelas, contratos, instituições)
7. Cruzar com o PRD seção 14 para mapear telas exatas
8. Classificar: 🟢 Vai usar / 🟡 Possivelmente / 🔴 Não vai usar
9. Definir prioridade e sprint planejada
10. Anotar dependências e notas de adaptação

**Ordem de criação (por ondas de prioridade):**

**Onda 1 (P0 — base do layout):**
Layouts (vertical, horizontal, boxed, fluid, skins), Navigation (sidebar com 6 tamanhos e 5 cores, topbar com 4 tipos, footer, breadcrumb, mega-menu)

**Onda 2 (P1 — feedback e dados):**
Data (KPI cards, widgets, metrics, status badges), Feedback (alerts, toasts, sweetalerts, modals, loading buttons, empty states, confirm dialogs)

**Onda 3 (P2 — CRUDs do admin):**
Tables (todas as 15+ variações de DataTable, custom tables exclusivas, static tables)
Forms (todos os 22 tipos: inputs, selects com Choices.js/Tagify/Select2, Flatpickr, Dropzone, FilePond, Inputmask, noUiSlider, wizard, validation, Quill editor, password meter, typeahead, touchspin)

**Onda 4 (P3 — visual e analytics):**
Charts/ApexCharts (20 tipos), Charts/ECharts (10 tipos), Dashboards (3: default, saas, analytics)

**Onda 5 — Pages:**
Auth (3 estilos × 11 páginas), Error (7 páginas × estilos), Utility (11 páginas), Apps (30+ páginas)

**Onda 6 — Extras:**
Plugins (sortable, clipboard, tree view, tour, animation, masonry, video player, idle timer, live favicon, text diff), Icons (Tabler, Lucide, Flags), Maps (Google, Vector, Leaflet)

**Reportar progresso a cada onda concluída com contagem: X/Y arquivos criados.**

### FASE 3 — Arquivos Índice e Mapa de Telas

#### 3.1 Catálogo com Links

Criar `.docs/INSPINIA-CATALOGO-COMPONENTES.md` — tabela resumo com link para cada doc:

```markdown
| # | Componente Blade | Categoria | Class. | Prior. | Status | Documentação |
|---|-----------------|-----------|:---:|:---:|:---:|---|
| 1 | x-admin.sidebar | Navigation | 🟢 | P0 | 🔴 | [→ docs](template/INSPINIA/Components/Navigation/sidebar.md) |
````

#### 3.2 Mapa Tela → Componentes

Criar `.docs/INSPINIA-MAPA-TELAS-COMPONENTES.md` — para CADA uma das 20+ telas do PRD seção 14 (14.1 a 14.21), listar os componentes com links:

```markdown
## 14.1 Login Admin (/admin/login)

| Componente                    | Doc Detalhada                                                |
| ----------------------------- | ------------------------------------------------------------ |
| x-admin.layout (auth variant) | [→](template/INSPINIA/Pages/Auth/sign-in-split.md)           |
| x-shared.input (email)        | [→](template/INSPINIA/Forms/input.md)                        |
| x-shared.input (password)     | [→](template/INSPINIA/Forms/input.md)                        |
| x-shared.loading-button       | [→](template/INSPINIA/Components/Feedback/loading-button.md) |
| x-shared.toggle (lembrar-me)  | [→](template/INSPINIA/Forms/toggle-switch.md)                |

## 14.2 Dashboard Admin (/admin)

| Componente             | Qtd | Doc Detalhada                                          |
| ---------------------- | :-: | ------------------------------------------------------ |
| x-admin.kpi-card       | ×4  | [→](template/INSPINIA/Components/Data/kpi-card.md)     |
| ApexCharts Bar         | ×1  | [→](template/INSPINIA/Charts/ApexCharts/bar.md)        |
| ApexCharts Line (dual) | ×1  | [→](template/INSPINIA/Charts/ApexCharts/line.md)       |
| x-admin.data-table     | ×3  | [→](template/INSPINIA/Tables/datatable-basic.md)       |
| x-admin.progress-bar   | ×N  | [→](template/INSPINIA/Components/UI/progress.md)       |
| x-admin.status-badge   | ×N  | [→](template/INSPINIA/Components/Data/status-badge.md) |
| x-admin.alert          | ×N  | [→](template/INSPINIA/Components/UI/alert.md)          |

[... continuar para TODAS as telas 14.3 a 14.21]
```

#### 3.3 Atualizar TEMPLATE-MAP-AND-COMPONENTS.md

Reescrever como índice geral que aponta para:

- `.docs/template/INSPINIA/README.md` (visão geral e inventário)
- `.docs/INSPINIA-CATALOGO-COMPONENTES.md` (catálogo com links)
- `.docs/INSPINIA-MAPA-TELAS-COMPONENTES.md` (mapa de telas)
- Hierarquia completa de `.docs/template/INSPINIA/`

### FASE 4 — Regras no CLAUDE.md e CONVENTIONS.md

Adicionar a seguinte regra em AMBOS os arquivos:

```
REGRA OBRIGATÓRIA — COMPONENTIZAÇÃO INSPINIA

Ao criar qualquer tela do admin, ANTES de escrever HTML:

1. CONSULTAR .docs/INSPINIA-CATALOGO-COMPONENTES.md (índice rápido)
2. Abrir a doc detalhada em .docs/template/INSPINIA/[categoria]/[nome].md
3. Verificar o status do componente:
   - 🟢 Pronto → USAR o componente Blade diretamente
   - 🟡 Em progresso → coordenar / aguardar
   - 🔴 Não criado → COMPONENTIZAR PRIMEIRO:
     a. Abrir a doc detalhada do item
     b. Seguir "Componente Blade Proposto" (código, props, slots)
     c. Criar o .blade.php em resources/views/components/
     d. Testar dark mode + responsividade
     e. Atualizar status para 🟢 no catálogo E na doc detalhada
     f. Só então usar na tela
4. Se o item NÃO existe no catálogo:
   a. Procurar componente equivalente no template Inspinia
   b. Criar a documentação detalhada em .docs/template/INSPINIA/
   c. Componentizar e documentar
   d. Adicionar ao catálogo
5. NUNCA escrever HTML cru que poderia ser um componente
6. NUNCA duplicar código — variações são feitas via @props
7. Se um elemento visual aparece em 2+ telas → DEVE ser componente
```

### FASE 5 — Triagem e Decomposição (Análise Antes de Componentizar)

> **Princípio:** O Inspinia traz páginas completas e exemplos montados (formulários de 20 campos, dashboards com 10 widgets, tabelas dentro de cards). O objetivo NÃO é componentizar páginas inteiras — é **decompor cada página em suas unidades atômicas reutilizáveis**, verificar se já existem, e só então criar o que falta.

#### 5.1 Regras de Triagem

Para CADA página/exemplo do Inspinia documentado na Fase 2, aplicar esta análise:

```
PÁGINA/EXEMPLO DO INSPINIA
  │
  ├── 1. DECOMPOR em unidades visuais atômicas
  │     Exemplo: Página "Form Wizard" do Inspinia contém:
  │     ├── Wizard container (stepper + navegação) → COMPONENTE: x-admin.wizard
  │     ├── Input de texto com label e erro → JÁ EXISTE: x-shared.input
  │     ├── Select searchable → JÁ EXISTE: x-shared.select-search
  │     ├── Datepicker → JÁ EXISTE: x-shared.date-picker
  │     ├── Upload de arquivo → JÁ EXISTE: x-shared.file-upload
  │     ├── Botão "Próximo" com loading → JÁ EXISTE: x-shared.loading-button
  │     └── Barra de progresso → JÁ EXISTE: x-admin.progress-bar
  │     RESULTADO: Só o wizard container é novo. O resto já existe.
  │
  ├── 2. Para cada unidade, VERIFICAR:
  │     ├── Já existe no catálogo com status 🟢? → REUSAR (não criar nada)
  │     ├── Já existe com status 🔴? → MARCAR para componentização
  │     ├── Existe algo similar? → AVALIAR se é variação (resolver via prop) ou novo componente
  │     └── Não existe nada parecido? → CRIAR nova entrada no catálogo + doc
  │
  ├── 3. CLASSIFICAR a unidade:
  │     ├── COMPONENTIZAR → É reutilizável (aparece em 2+ telas do PRD)
  │     ├── NÃO COMPONENTIZAR → É específico demais (só serve para este exemplo)
  │     ├── VARIAÇÃO → É uma variação de componente existente (adicionar prop)
  │     └── COMPOSIÇÃO → É um agrupamento de componentes existentes (montar na view)
  │
  └── 4. DOCUMENTAR a decisão na doc detalhada do item
```

#### 5.2 O Que NÃO Componentizar

```
❌ Páginas inteiras — Um formulário de cadastro de 15 campos NÃO é um componente.
   Os CAMPOS individuais são componentes. O formulário é uma VIEW que compõe componentes.

❌ Exemplos demo — A página "E-commerce Product Detail" do Inspinia mostra um produto
   com fotos, preço, avaliações. Esse layout NÃO é um componente do projeto.
   Mas o card de imagem com zoom, o badge de status e o input de quantidade SIM podem ser.

❌ Layouts de exemplo — O Inspinia mostra 3 estilos de login. NÃO criar 3 componentes.
   Escolher UM estilo (Split) e componentizar apenas esse.

❌ Variações que viram prop — Se existe um botão azul e um vermelho, NÃO criar
   x-shared.button-blue e x-shared.button-red. Criar UM x-shared.button com prop color.

❌ Combinações óbvias — Card com tabela dentro NÃO é um componente novo.
   É <x-admin.card> com <x-admin.data-table> dentro via {{ $slot }}.
```

#### 5.3 O Que SIM Componentizar

```
✅ Unidades atômicas reutilizáveis — Input, select, datepicker, badge, alert, modal, toast

✅ Padrões visuais recorrentes — KPI card (aparece no dashboard, relatórios, ficha do formando)

✅ Interações complexas encapsuladas — DataTable com busca/filtro/paginação/ordenação,
   wizard com stepper e navegação, drawer lateral com formulário

✅ Elementos com lógica de estado — Status badge que muda cor pelo Enum,
   loading button que mostra spinner, empty state com mensagem e ação

✅ Wrappers de plugins JS — Flatpickr precisa de inicialização JS → componente
   Choices.js precisa de config → componente. Inputmask precisa de padrão → componente
```

#### 5.4 Exemplo Prático de Triagem

A página "Form Wizard" do Inspinia tem um wizard de 4 etapas com vários campos.

**Análise de decomposição:**

| Elemento Visual                     | Decisão              | Motivo                                                      | Ação                                         |
| ----------------------------------- | -------------------- | ----------------------------------------------------------- | -------------------------------------------- |
| Página completa do wizard           | ❌ Não componentizar | É uma page inteira, não componente                          | Será uma VIEW                                |
| Container do wizard (stepper + nav) | ✅ Componentizar     | Reutilizável: wizard de adesão (7 etapas) + cadastro manual | Criar `x-admin.wizard`                       |
| Stepper/barra de progresso          | ✅ Componentizar     | Reutilizável no portal (wizard) e admin (cadastro manual)   | Criar `x-shared.wizard-progress`             |
| Input "Nome"                        | ❌ Já existe         | É um input text padrão                                      | Reusar `x-shared.input`                      |
| Input "Email"                       | ❌ Já existe         | É um input email padrão                                     | Reusar `x-shared.input` com type="email"     |
| Select "País"                       | ❌ Já existe         | É um select searchable                                      | Reusar `x-shared.select-search`              |
| Datepicker "Data Nascimento"        | ❌ Já existe         | É um flatpickr                                              | Reusar `x-shared.date-picker`                |
| Upload "Avatar"                     | ❌ Já existe         | É um dropzone                                               | Reusar `x-shared.file-upload`                |
| Checkbox "Aceito os termos"         | ❌ Já existe         | É um checkbox padrão                                        | Reusar `x-shared.checkbox`                   |
| Botão "Próximo"                     | ❌ Já existe         | É um loading button                                         | Reusar `x-shared.loading-button`             |
| Botão "Voltar"                      | ❌ Já existe         | É um button secondary                                       | Reusar `x-shared.button` variant="secondary" |
| Layout 2 colunas do form            | ❌ Não componentizar | É grid CSS, não componente                                  | Usar classes Tailwind direto                 |

**Resultado:** De 12 elementos, só 2 são componentes novos. O resto é reuso ou Tailwind direto.

**Enriquecimento da doc:** Na documentação do `x-admin.wizard`, adicionar na seção "Onde aparece no Inspinia" que o HTML de referência veio da página Form Wizard, e incluir na seção "Casos de Uso" que pode ser usado para o wizard de adesão (7 etapas) e para o cadastro manual de formando (8 seções).

#### 5.5 Arquivo de Triagem

Salvar o resultado em: `.docs/INSPINIA-TRIAGEM-COMPONENTES.md`

```markdown
# Triagem de Componentes — Inspinia → Portal ArtFinal

## Resumo

| Decisão                                  | Quantidade |
| ---------------------------------------- | :--------: |
| ✅ Componentizar (novo)                  |     XX     |
| ♻️ Reusar (já existe)                    |     XX     |
| ➕ Variação (adicionar prop a existente) |     XX     |
| 🧩 Composição (montar na view)           |     XX     |
| ❌ Não componentizar (específico demais) |     XX     |

## Detalhamento por Página/Exemplo

### Forms / Form Wizard

| Elemento         | Decisão  | Componente               | Origem          |
| ---------------- | -------- | ------------------------ | --------------- |
| Wizard container | ✅ Novo  | x-admin.wizard           | Forms/wizard.md |
| Stepper          | ✅ Novo  | x-shared.wizard-progress | Forms/wizard.md |
| Input nome       | ♻️ Reuso | x-shared.input           | Forms/input.md  |
| ...              |          |                          |                 |

### Pages / E-commerce Products

| Elemento           | Decisão       | Componente                   | Origem                          |
| ------------------ | ------------- | ---------------------------- | ------------------------------- |
| Grid de produtos   | 🧩 Composição | Tailwind grid + x-admin.card | —                               |
| Badge "Em estoque" | ♻️ Reuso      | x-admin.status-badge         | Components/Data/status-badge.md |
| ...                |               |                              |                                 |

[... para CADA página/exemplo analisado]
```

#### 5.6 Regra de Enriquecimento

Ao documentar cada componente na Fase 2, SEMPRE incluir:

```markdown
## Origem e Contexto no Inspinia

Este componente foi identificado a partir das seguintes páginas do Inspinia:

| Página de Origem      | Como Aparece                   | O Que Foi Extraído                |
| --------------------- | ------------------------------ | --------------------------------- |
| Forms / Form Wizard   | Campo de data dentro do step 2 | Estrutura do datepicker com label |
| Pages / Profile       | Campo de data de nascimento    | Variação com formato dd/mm/yyyy   |
| Apps / Invoice Create | Campo de data de vencimento    | Variação com min date = hoje      |

### Sugestões de Uso no Projeto (derivadas dos exemplos do Inspinia)

- No wizard de adesão, etapa 4 (cadastro): data de nascimento do formando
- No admin, cadastro de contrato: data início e data evento
- No admin, programações: data início e data fim da programação
- No extrato financeiro: filtro por range de datas

### Como Era Usado no Inspinia (contexto original)

[Descrever brevemente como o componente estava montado na página original,
para que o dev entenda a intenção visual e possa se inspirar ao montar suas telas]
```

---

### FASE 6 — Agente de Componentização (Refatoração Automática)

> **Pré-requisito:** A Fase 5 (Triagem) deve estar concluída. O agente só componentiza itens marcados como "✅ Componentizar" na triagem.

Esta fase transforma a documentação em **código real**. O agente percorre os componentes aprovados na triagem e cria os arquivos Blade/Livewire prontos para usar.

#### 6.1 Regras do Agente

O agente de componentização segue este fluxo para CADA componente aprovado na triagem:

```
ENTRADA: .docs/template/INSPINIA/[Categoria]/[nome].md
  │
  ├── 1. Ler a documentação detalhada do componente
  │
  ├── 2. Identificar o tipo de componente:
  │     ├── Blade anônimo → componentes visuais simples (badge, alert, card, button)
  │     ├── Blade class-based → componentes com lógica PHP (data-table, filter-panel)
  │     └── Livewire → componentes com interação servidor (forms dinâmicos, tabelas com filtro)
  │
  ├── 3. Extrair o HTML limpo da seção "Código Original (Inspinia)"
  │
  ├── 4. Transformar em componente Blade:
  │     ├── Definir @props com tipos e defaults
  │     ├── Converter conteúdo fixo em {{ $slot }} e slots nomeados
  │     ├── Converter variações em props condicionais (@class, @if)
  │     ├── Garantir dark mode (dark: classes em todo elemento com cor)
  │     ├── Garantir responsividade (sm:, md:, lg: onde necessário)
  │     ├── Integrar plugin JS se necessário (Alpine.js x-data, x-show, etc.)
  │     └── Remover qualquer resquício de Bootstrap ou conteúdo demo
  │
  ├── 5. Criar os arquivos:
  │     ├── resources/views/components/[admin|shared]/[nome].blade.php
  │     ├── app/View/Components/[Admin|Shared]/[Nome].php (se class-based)
  │     └── app/Livewire/Components/[Nome].php (se Livewire)
  │
  ├── 6. Criar página de preview/teste:
  │     └── resources/views/admin/dev/components/[nome].blade.php
  │     (página temporária para visualizar o componente isolado durante dev)
  │
  ├── 7. Atualizar documentação:
  │     ├── Status no .md: 🔴 → 🟢
  │     ├── Adicionar seção "Código Final" no .md com o Blade real criado
  │     └── Atualizar .docs/INSPINIA-CATALOGO-COMPONENTES.md (status 🟢)
  │
  └── SAÍDA: Componente Blade pronto para usar com <x-admin.nome> ou <x-shared.nome>
```

#### 6.2 Ordem de Componentização (por onda)

A componentização segue a mesma ordem das ondas da Fase 2, mas agora criando código real:

**Onda 1 — P0 (Sprint 1-3): Layout Base**
Estes são pré-requisito para TUDO. Criar primeiro:

```
resources/views/components/
├── admin/
│   ├── layout.blade.php            ← Layout master (sidebar + topbar + main + footer)
│   ├── sidebar.blade.php           ← Menu lateral colapsável, 6 tamanhos, 5 cores
│   ├── topbar.blade.php            ← Header com breadcrumb, user dropdown, notificações
│   ├── breadcrumb.blade.php        ← Navegação hierárquica
│   ├── page-header.blade.php       ← Título da página + botões de ação
│   └── footer.blade.php            ← Rodapé simples
```

**Onda 2 — P1 (Sprint 4-14): Dados e Feedback**

```
├── admin/
│   ├── kpi-card.blade.php          ← Card de métrica com ícone, valor, trend
│   ├── status-badge.blade.php      ← Badge colorido baseado em Enum
│   ├── chart-card.blade.php        ← Wrapper para gráficos ApexCharts
│   ├── confirm-modal.blade.php     ← Modal de confirmação com ação
│   ├── drawer-form.blade.php       ← Formulário lateral (offcanvas)
│   ├── notification-bell.blade.php ← Sino com badge de contagem
│   ├── timeline-item.blade.php     ← Item de auditoria/histórico
│   └── tabs.blade.php              ← Sistema de tabs com painel
├── shared/
│   ├── alert.blade.php             ← Alerta (4 tipos: success, error, warning, info)
│   ├── toast.blade.php             ← Toast notification
│   ├── button.blade.php            ← Botão (variantes: primary, secondary, danger, etc.)
│   ├── loading-button.blade.php    ← Botão com wire:loading
│   ├── empty-state.blade.php       ← Estado vazio (sem dados)
│   └── confirm-dialog.blade.php    ← SweetAlert2 wrapper
```

**Onda 3 — P2 (Sprint 15-19): Formulários e Tabelas**

```
├── shared/
│   ├── input.blade.php             ← Input text com label, erro, help
│   ├── textarea.blade.php
│   ├── select.blade.php            ← Select nativo
│   ├── select-search.blade.php     ← Choices.js
│   ├── toggle.blade.php            ← Toggle switch
│   ├── checkbox.blade.php
│   ├── radio.blade.php
│   ├── money-input.blade.php       ← Inputmask monetário BR
│   ├── cpf-input.blade.php         ← Inputmask CPF
│   ├── cnpj-input.blade.php        ← Inputmask CNPJ
│   ├── cep-input.blade.php         ← CEP + autocomplete ViaCEP
│   ├── phone-input.blade.php       ← Inputmask telefone BR
│   ├── date-picker.blade.php       ← Flatpickr
│   ├── date-range-picker.blade.php ← Flatpickr range
│   ├── file-upload.blade.php       ← Dropzone
│   ├── tags-input.blade.php        ← Tagify
│   └── password-input.blade.php    ← Toggle visibilidade + medidor força
├── admin/
│   ├── data-table.blade.php        ← DataTable com busca, filtros, paginação
│   ├── filter-panel.blade.php      ← Painel de filtros colapsável
│   ├── action-dropdown.blade.php   ← Menu dropdown de ações por linha
│   ├── bulk-actions.blade.php      ← Seleção múltipla + ações
│   └── export-buttons.blade.php    ← CSV/Excel
```

**Onda 4 — P3 (Sprint 20-24): Especializados**

```
├── admin/
│   ├── accordion.blade.php         ← Sections colapsáveis (cadastro manual)
│   ├── sortable-list.blade.php     ← Drag-and-drop (termos)
│   ├── progress-bar.blade.php      ← Meta formandos
│   ├── formando-card.blade.php     ← Card resumo formando (foto, nome, status)
│   ├── parcela-row.blade.php       ← Linha de parcela com ações
│   └── programacao-timeline.blade.php ← Timeline visual de programações
├── portal/
│   ├── layout.blade.php            ← Layout master portal (mobile-first)
│   ├── header.blade.php
│   ├── footer.blade.php
│   ├── wizard-progress.blade.php   ← Barra de progresso 7 etapas
│   ├── package-card.blade.php      ← Card de pacote/produto
│   ├── parcela-card.blade.php      ← Card de parcela no extrato
│   └── formando-selector.blade.php ← Seletor multi-formando
```

#### 6.3 Template de Execução por Componente

Para cada componente, o agente executa:

```
Componentize o item [NOME] do Inspinia.

1. Leia a documentação detalhada em:
   .docs/template/INSPINIA/[Categoria]/[nome].md

2. Tipo de componente: [Blade anônimo | Class-based | Livewire]
   (decidir com base na complexidade — se tem lógica PHP → class-based,
   se tem interação com servidor → Livewire, caso contrário → anônimo)

3. Crie o arquivo Blade em:
   resources/views/components/[admin|shared]/[nome].blade.php

   Seguindo as regras:
   - @props com tipos e defaults
   - {{ $slot }} para conteúdo dinâmico
   - Dark mode: toda cor tem variante dark:
   - Responsividade: sm:/md:/lg: onde faz sentido
   - Se usa plugin JS: integrar via Alpine.js (x-data, x-init, x-show)
   - Sem Bootstrap, sem jQuery, sem JS inline
   - Classes Tailwind CSS 4 puras

4. Se class-based, crie:
   app/View/Components/[Admin|Shared]/[NomePascal].php

5. Se Livewire, crie:
   app/Livewire/Components/[NomePascal].php

6. Crie um teste visual temporário em:
   resources/views/admin/dev/components/[nome].blade.php
   (página simples que renderiza o componente com dados mock)

7. Atualize a documentação:
   - No .md do componente: status → 🟢, adicionar seção "Código Final Blade"
   - No catálogo: status → 🟢

8. Confirme: <x-[admin|shared].[nome]> está funcionando e documentado.
```

#### 6.4 Slash Command para Componentização

Criar `.claude/commands/componentize.md`:

```markdown
Componentize o item $ARGUMENTS do Inspinia para o projeto Portal ArtFinal.

1. Leia a doc detalhada em .docs/template/INSPINIA/ (buscar pelo nome $ARGUMENTS)
2. Se a doc não existir, primeiro documente o componente seguindo o template padrão
3. Extraia o HTML limpo da seção "Código Original"
4. Determine o tipo: Blade anônimo, class-based, ou Livewire
5. Crie o componente Blade em resources/views/components/[admin|shared]/
6. Crie classe PHP se necessário
7. Garanta: dark mode, responsividade, @props tipados, {{ $slot }}
8. Crie página de preview em resources/views/admin/dev/components/
9. Atualize status para 🟢 na doc e no catálogo
10. Rode ./vendor/bin/pint no arquivo criado

Siga os padrões de .docs/CONVENTIONS.md e .docs/ARCHITECTURE-GUIDE.md.
```

**Uso:** `/componentize kpi-card` → cria o componente x-admin.kpi-card completo

#### 6.5 Slash Command para Componentização em Lote

Criar `.claude/commands/componentize-wave.md`:

```markdown
Componentize todos os itens da Onda $ARGUMENTS do Inspinia.

Ondas disponíveis:

- 1: Layout Base (layout, sidebar, topbar, breadcrumb, page-header, footer)
- 2: Dados e Feedback (kpi-card, status-badge, alert, toast, modal, button, etc.)
- 3: Formulários e Tabelas (inputs, selects, datepickers, data-table, etc.)
- 4: Especializados (accordion, sortable, charts, portal components)

Para cada componente da onda:

1. Ler a doc em .docs/template/INSPINIA/
2. Criar o componente Blade
3. Criar preview
4. Atualizar status para 🟢
5. Reportar: [✅ nome] ou [❌ nome — motivo]

Ao final, listar todos os componentes criados e atualizar o catálogo.
```

**Uso:** `/componentize-wave 1` → cria todos os 6 componentes de layout de uma vez

---

### FASE 7 — Resumo Executivo

Ao final de TODAS as fases, gerar `.docs/INSPINIA-RESUMO-EXECUTIVO.md` com:

1. **Checklist de specs preenchida** (✅/❌ para cada feature do Inspinia)
2. **Totais:** X componentes documentados, Y classificados como 🟢, Z como 🟡, W como 🔴
3. **Plugins JS necessários:** nome, versão, onde usar
4. **Plugins JS descartados:** nome, motivo
5. **Estimativa de esforço por onda:** horas estimadas
6. **Ordem de componentização:** P0 → P1 → P2 → P3 com sprints
7. **Riscos:** componentes complexos, conflitos JS, dependências problemáticas
8. **Itens não encontrados:** features que as specs prometem mas não existem na versão Tailwind/Laravel

Relate cada fase: ✅ Concluído | ⏳ Em Andamento | ❌ Bloqueado (motivo)

---

## GESTÃO DE TAREFAS (OBRIGATÓRIO)

Toda tarefa gerada por este prompt DEVE ser registrada no **Plane** (via MCP ou manualmente).

### Regra

1. **Cada Fase** deste prompt gera um **Módulo/Épico** no Plane
2. **Cada Onda** dentro de uma Fase gera um **Cycle (Sprint)** no Plane
3. **Cada componente** a documentar/triar/componentizar gera uma **Issue** no Plane
4. O progresso é atualizado no Plane conforme cada item é concluído
5. Issues bloqueadas devem ter o motivo registrado como comentário no Plane

### Estrutura no Plane

Módulo: Análise e Componentização Inspinia
├── Cycle: Fase 1 — Inventário
│ ├── Issue: Percorrer estrutura do template
│ ├── Issue: Ler documentação oficial
│ ├── Issue: Validar checklist de specs
│ └── Issue: Criar README.md mestre
├── Cycle: Fase 2 — Documentação Onda 1 (P0)
│ ├── Issue: Documentar layout vertical
│ ├── Issue: Documentar sidebar
│ ├── Issue: Documentar topbar
│ └── ...
├── Cycle: Fase 5 — Triagem
│ ├── Issue: Triar Forms (wizard, validation, etc.)
│ ├── Issue: Triar Tables (DataTables)
│ └── ...
├── Cycle: Fase 6 — Componentização Onda 1
│ ├── Issue: Componentizar x-admin.layout
│ ├── Issue: Componentizar x-admin.sidebar
│ └── ...
└── Cycle: Fase 7 — Resumo Executivo
└── Issue: Gerar INSPINIA-RESUMO-EXECUTIVO.md

### Labels para estas tarefas

- `chore` + `docs` → Fases 1-4 (documentação e análise)
- `chore` + `admin` → Fases 5-6 (triagem e componentização)
- `mod:ui` → Todas as issues deste prompt

**NUNCA executar uma fase sem registrar as tarefas no Plane primeiro. O Plane é a fonte de verdade do progresso.**
