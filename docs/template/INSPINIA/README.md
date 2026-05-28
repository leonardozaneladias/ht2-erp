# Inspinia v5.0 — Inventário Mestre (Fase 1)

> **Template:** Inspinia v5.0 — Laravel + Tailwind CSS 4
> **Path:** `~/Projects/Templates/Laravel/INSPINIA_v5.0/Tailwind CSS/Laravel/inspinia`
> **Doc oficial:** `~/Projects/Templates/Laravel/INSPINIA_v5.0/Tailwind CSS/Laravel/Docs/index.html`
> **Análise gerada em:** 2026-04-11 (Fase 1 do prompt `docs/prompts/PROMPT-ANALISE-INSPINIA.md`)

Este documento é o **ponto de entrada** para toda a documentação granular do Inspinia no Portal ArtFinal. Ele contém:

1. Visão geral do template e versões detectadas
2. Inventário quantitativo (páginas, apps, componentes)
3. Checklist de specs validada (✅/❌)
4. Plugins JS/CSS confirmados
5. Skin recomendada para o projeto
6. Itens não encontrados ou desatualizados
7. Índice para a documentação granular (a ser criada nas Fases 2–6)

---

## 1. Visão Geral

Inspinia é um admin dashboard multiúso distribuído pela WebAppLayers. A versão v5.0 (março/2026) adota **Tailwind CSS 4** como base de estilização e usa **Preline** como JS framework para componentes interativos. A variante Laravel usa **Blade templating** com estrutura `@extends('shared.base') → @section('content')`.

### Stack do template (package.json)

| Camada          | Tecnologia                                                                           | Versão                                                               |
| --------------- | ------------------------------------------------------------------------------------ | -------------------------------------------------------------------- |
| Build           | Vite                                                                                 | `^7.2.7`                                                             |
| Plugin Laravel  | laravel-vite-plugin                                                                  | `^2.0.0`                                                             |
| CSS base        | tailwindcss                                                                          | `^4.1.18`                                                            |
| CSS plugins     | @tailwindcss/forms, @tailwindcss/typography, @tailwindcss/postcss, @tailwindcss/vite | `0.5.11 / 0.5.19 / 4.1.18 / 4.1.18`                                  |
| UI framework JS | **preline**                                                                          | `4.0.1`                                                              |
| jQuery          | jquery                                                                               | `3.7.1` (requerido por DataTables/Select2/Typeahead/Daterangepicker) |
| Package manager | Bun                                                                                  | (recomendado pela doc oficial)                                       |

> **Importante:** A dependência oficial em Preline significa que TODOS os componentes interativos "nativos" do Inspinia (dropdowns, accordions, modals, offcanvas, tabs, tooltips, popovers, tabs, stepper) usam `data-hs-*` attributes e JS do Preline. Ao portar para o Portal ArtFinal precisamos decidir: manter Preline, ou reescrever em Alpine.js/Livewire. **Recomendação:** manter Preline para economizar trabalho — ele é compatível com Tailwind 4 e Laravel.

---

## 2. Contagens Oficiais vs Encontradas

| Item                  | Oficial (Docs) | Spec do prompt |                                                    Encontrado                                                    |        Status        |
| --------------------- | :------------: | :------------: | :--------------------------------------------------------------------------------------------------------------: | :------------------: |
| Pré-built pages       |    **218**     |      235+      |                                            240 arquivos `.blade.php`                                             | ✅ (inclui partials) |
| Skins                 |     **11**     |       6        |                                               11 (config options)                                                |          ✅          |
| Sidenav sizes         |     **6**      |       6        |                      6 (default, compact, condensed, on-hover, on-hover-active, offcanvas)                       |          ✅          |
| Sidenav colors        |     **5**      |       5        |                                      5 (light, dark, gray, gradient, image)                                      |          ✅          |
| Topbar colors         |     **4**      |       4        |                                         4 (light, dark, gray, gradient)                                          |          ✅          |
| Dashboards            | não especifica |       3        |                                           **2** (analytics, ecommerce)                                           |          ⚠️          |
| Auth styles           |       —        |       3        |                                     3 (`auth/`, `auth-card/`, `auth-split/`)                                     |          ✅          |
| Auth pages/style      |       —        |       11       | **9** (sign-in, sign-up, reset-pass, new-pass, two-factor, lock-screen, success-mail, login-pin, delete-account) |          ⚠️          |
| Error pages           |       —        |       7        |                                  7 (400, 401, 403, 404, 408, 500, maintenance)                                   |          ✅          |
| ApexCharts types      |       —        |       20       |                                                        20                                                        |          ✅          |
| ECharts types         |       —        |       10       |                                          11 (inclui `other.blade.php`)                                           |          ✅          |
| DataTables variants   |       —        |      15+       |                                                        15                                                        |          ✅          |
| Apps distintos        |       —        |      15+       |                                                        20                                                        |          ✅          |
| UI components (views) |       —        |       —        |                                                        27                                                        |          ✅          |
| Form demo pages       |       —        |       —        |                                                        10                                                        |          ✅          |
| Utility pages         |       —        |       —        |                                                        12                                                        |          ✅          |
| Plugins pages         |       —        |       —        |                                                        15                                                        |          ✅          |

### Totais por categoria (view count)

```
apps/               46  (distribuído em 7 subpastas + 14 arquivos raiz)
auth/auth-card/auth-split/  27  (3 estilos × 9 páginas)
charts/             31  (apex: 20 + echart: 11)
dashboard/          2   (analytics, ecommerce)
error/              7
form/               10
icons/              3   (flags, lucide, tabler)
layouts/            17  (5 bases + 9 sidebar variants + 3 topbar variants)
maps/               3   (google, leaflet, vector)
pages/              12
plugins/            15
shared/             11  (base, horizontal, vertical, partials/*8)
tables/             17  (custom, static, datatables/*15)
ui/                 27
index.blade.php     1
────────────────────────
TOTAL              240 arquivos .blade.php
```

---

## 3. Checklist de Specs do Prompt (validada)

Referência: seção "SPECS DO TEMPLATE" do `docs/prompts/PROMPT-ANALISE-INSPINIA.md`.

### 3.1 Features Gerais

| Item                               | Status | Observação                                                                                                  |
| ---------------------------------- | :----: | ----------------------------------------------------------------------------------------------------------- |
| Built using Bootstrap (v5.3.8)     |   ❌   | A versão Tailwind NÃO usa Bootstrap. Spec é do template Bootstrap original.                                 |
| Powered by Tailwind CSS (v4.2.x)   |   ⚠️   | Versão instalada é `^4.1.18` (aceita 4.2.x mas atual é 4.1).                                                |
| Responsive Layout                  |   ✅   | Classes `sm:` `md:` `lg:` em todo o template.                                                               |
| Light & Dark Theme (CSS Based)     |   ✅   | `<html data-theme="dark">` ativa.                                                                           |
| 6 Skins                            |   ⚠️   | Oficial tem **11 skins**: default, minimal, modern, material, saas, flat, galaxy, luxe, retro, neon, pixel. |
| SCSS Variables                     |   ❌   | A versão Tailwind usa **CSS custom properties** em `resources/css/config/_root.css`, não SCSS.              |
| Vertical & Horizontal Layouts      |   ✅   | `orientation: vertical/horizontal` no defaultConfig.                                                        |
| 235+ Pages                         |   ⚠️   | Oficial: 218 pages. Encontrados 240 .blade.php (inclui partials/layouts).                                   |
| 15+ Apps                           |   ✅   | 20 apps distintos encontrados.                                                                              |
| 4 Types of Topbar                  |   ✅   | light, dark, gray, gradient.                                                                                |
| 6 Types of Sidenav Sizes           |   ✅   | default, compact, condensed, on-hover, on-hover-active, offcanvas.                                          |
| 5 Types of Sidenav Colors          |   ✅   | light, dark, gray, gradient, image.                                                                         |
| Fixed & Scrollable                 |   ✅   | `position: fixed/scrollable`.                                                                               |
| Sidebar with User Support          |   ✅   | `sidenav-user: true/false`.                                                                                 |
| TanStack Tables Support            |   ❌   | **NÃO existe na versão Laravel/Tailwind** (só nas variantes React/Angular/Next).                            |
| Widgets & Metrics Pages            |   ✅   | Presente nos dashboards.                                                                                    |
| Fluid & Boxed View                 |   ✅   | `width: fluid/boxed`.                                                                                       |
| Auth & Error Pages (3 styles)      |   ✅   | Basic (`auth/`), Card (`auth-card/`), Split (`auth-split/`).                                                |
| Font-Based Icons (Tabler & Lucide) |   ✅   | Via `@iconify/json` + `@iconify/tailwind4` e `lucide` npm package.                                          |
| Google Fonts                       |   ✅   | Carregadas em `shared/partials/head-css.blade.php`.                                                         |
| W3C Validated Code                 |   ⚠️   | Não verificável sem execução.                                                                               |
| 3 Unique Dashboards                |   ❌   | **Apenas 2 dashboards** na versão atual: `analytics`, `ecommerce`. (Spec menciona 3.)                       |
| Landing Page                       |   ❌   | Não encontrado arquivo `landing.blade.php`.                                                                 |
| i18n Support                       |   ✅   | `plugins/i18.blade.php` + json translations em `public/data/translations/`.                                 |

### 3.2 Apps (contando a partir de `resources/views/apps/`)

| Item                                                        | Status | Arquivo                                                                                                                                                                                                                                                                                                           |
| ----------------------------------------------------------- | :----: | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Email App                                                   |   ✅   | `apps/email/inbox.blade.php`, `compose.blade.php`, `details.blade.php`                                                                                                                                                                                                                                            |
| E-commerce (completo)                                       |   ✅   | 23 páginas em `apps/ecommerce/` (marketplace, products, products-grid, product-details, product-add, cart, checkout, orders, order-add, order-details, customers, sellers, seller-details, reviews, refunds, sales, attributes, categories, warehouse, purchased-orders, product-stocks, product-views, settings) |
| Users (Contacts, Roles, Permissions)                        |   ✅   | `apps/users/` (contacts, roles, role-details, permissions)                                                                                                                                                                                                                                                        |
| Projects (list, grid, detail, team board, activity, kanban) |   ✅   | `apps/projects/` (list, grid, details, team-board, activity, kanban)                                                                                                                                                                                                                                              |
| File Manager                                                |   ✅   | `apps/file-manager.blade.php`                                                                                                                                                                                                                                                                                     |
| Chat                                                        |   ✅   | `apps/chat.blade.php`                                                                                                                                                                                                                                                                                             |
| Calendar                                                    |   ✅   | `apps/calendar.blade.php`                                                                                                                                                                                                                                                                                         |
| Social Feed                                                 |   ✅   | `apps/social-feed.blade.php`                                                                                                                                                                                                                                                                                      |
| Invoice Management (list, details, create)                  |   ✅   | `apps/invoice/` (list, details, create)                                                                                                                                                                                                                                                                           |
| Outlook View                                                |   ✅   | `apps/outlook.blade.php`                                                                                                                                                                                                                                                                                          |
| Companies List                                              |   ✅   | `apps/companies.blade.php`                                                                                                                                                                                                                                                                                        |
| Clients                                                     |   ✅   | `apps/clients.blade.php`                                                                                                                                                                                                                                                                                          |
| Vote List                                                   |   ✅   | `apps/vote-list.blade.php`                                                                                                                                                                                                                                                                                        |
| Issue Tracker                                               |   ✅   | `apps/issue-tracker.blade.php`                                                                                                                                                                                                                                                                                    |
| API Keys                                                    |   ✅   | `apps/api-keys.blade.php`                                                                                                                                                                                                                                                                                         |
| Blog (grid, list, detail, add)                              |   ✅   | `apps/blog/` (grid, list, article, add)                                                                                                                                                                                                                                                                           |
| Pin Board                                                   |   ✅   | `apps/pin-board.blade.php`                                                                                                                                                                                                                                                                                        |
| Forum (view + post)                                         |   ✅   | `apps/forum/` (view, post)                                                                                                                                                                                                                                                                                        |
| PDF Viewer                                                  |   ✅   | `plugins/pdf-viewer.blade.php` (em plugins, não apps)                                                                                                                                                                                                                                                             |
| Idle Timer                                                  |   ✅   | `plugins/idle-timer.blade.php`                                                                                                                                                                                                                                                                                    |
| Live Favicon                                                |   ✅   | `plugins/live-favicon.blade.php`                                                                                                                                                                                                                                                                                  |
| Text Diff                                                   |   ✅   | `plugins/text-diff.blade.php`                                                                                                                                                                                                                                                                                     |

### 3.3 Components

| Item                                         | Status | Arquivo/Plugin                                                                                                                                                                                                      |
| -------------------------------------------- | :----: | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| All Bootstrap Components                     |   ❌   | Não usa Bootstrap. Equivalentes em `ui/*.blade.php` usando Tailwind + Preline.                                                                                                                                      |
| Loading buttons                              |   ✅   | `plugins/loading-buttons.blade.php` (usa Ladda)                                                                                                                                                                     |
| Text Editors (Quilljs & Summernote)          |   ⚠️   | Quill sim (`form/text-editors.blade.php`). Summernote **NÃO** (removido nesta versão).                                                                                                                              |
| Form Basic Components                        |   ✅   | `form/elements.blade.php`, `form/layout.blade.php`                                                                                                                                                                  |
| Form Validations                             |   ✅   | `form/validation.blade.php`                                                                                                                                                                                         |
| Form Wizard (Custom)                         |   ✅   | `form/wizard.blade.php`                                                                                                                                                                                             |
| Pickers (Date Range, Flatpickr, Colorpicker) |   ✅   | `form/pickers.blade.php` (Flatpickr + Daterangepicker + Pickr)                                                                                                                                                      |
| Select (Choices.js, Tagify, Select2)         |   ⚠️   | Choices.js ✅, Select2 ✅ (`form/select.blade.php`). **Tagify NÃO encontrado** no package.json.                                                                                                                     |
| File Upload (Dropzone & FilePond)            |   ✅   | `form/fileuploads.blade.php`                                                                                                                                                                                        |
| noUiSlider Range Slider                      |   ✅   | `form/range-slider.blade.php`                                                                                                                                                                                       |
| Inputmask                                    |   ✅   | `form/other-plugin.blade.php` (inputmask é um dos plugins dessa página)                                                                                                                                             |
| Typeahead                                    |   ✅   | `form/other-plugin.blade.php` (typeahead.js incluído)                                                                                                                                                               |
| Input Touchspin                              |   ⚠️   | **Não encontrado como plugin dedicado**. Spec do prompt está desatualizada.                                                                                                                                         |
| Bootstrap Carousel                           |   ⚠️   | É `ui/carousel.blade.php` mas baseado em Preline/custom, não Bootstrap.                                                                                                                                             |
| Bootstrap Tables                             |   ❌   | É `tables/static.blade.php` com Tailwind puro.                                                                                                                                                                      |
| Custom Tables (Exclusive in INSPINIA)        |   ✅   | `tables/custom.blade.php`                                                                                                                                                                                           |
| 15+ Datatables                               |   ✅   | 15 variantes em `tables/datatables/` (ajax, basic, checkbox-select, child-rows, column-searching, columns, export-data, fixed-columns, fixed-header, javascript, range-search, rendering, rows-add, scroll, select) |
| Tabler & Lucide Icons                        |   ✅   | `icons/tabler.blade.php`, `icons/lucide.blade.php`                                                                                                                                                                  |
| All Countries SVG Flags                      |   ✅   | `icons/flags.blade.php`                                                                                                                                                                                             |
| Google, Vector, Leaflet Maps                 |   ⚠️   | `maps/google.blade.php` existe mas **NÃO há Google Maps no package.json** — provavelmente usa iframe/embed. JSVectorMap e Leaflet confirmados.                                                                      |

### 3.4 Pages (Utility)

| Item                 | Status | Arquivo                                                 |
| -------------------- | :----: | ------------------------------------------------------- |
| Profile Page         |   ✅   | `pages/profile.blade.php`                               |
| Account Settings     |   ✅   | `pages/account-settings.blade.php` (não estava na spec) |
| FAQ                  |   ✅   | `pages/faq.blade.php`                                   |
| Pricing              |   ✅   | `pages/pricing.blade.php`                               |
| Timeline             |   ✅   | `pages/timeline.blade.php`                              |
| Search Results       |   ✅   | `pages/search-results.blade.php`                        |
| Starter Page (Empty) |   ✅   | `pages/empty.blade.php`                                 |
| Coming Soon          |   ✅   | `pages/coming-soon.blade.php`                           |
| Terms & Conditions   |   ✅   | `pages/terms-conditions.blade.php`                      |
| Privacy Policy       |   ✅   | `pages/privacy-policy.blade.php` (não estava na spec)   |
| Sitemap              |   ✅   | `pages/sitemap.blade.php` (não estava na spec)          |
| Sortable List        |   ✅   | `plugins/sortable.blade.php`                            |
| SweetAlerts 2        |   ✅   | `plugins/sweet-alerts.blade.php`                        |
| Password Meter       |   ✅   | `plugins/pass-meter.blade.php`                          |
| Clipboard            |   ✅   | `plugins/clipboard.blade.php`                           |
| Tree View            |   ✅   | `plugins/tree-view.blade.php`                           |
| Gallery              |   ✅   | `pages/gallery.blade.php`                               |
| Masonry Support      |   ✅   | `plugins/masonry.blade.php`                             |
| Tour Page            |   ✅   | `plugins/tour.blade.php`                                |
| Animate CSS Support  |   ✅   | `plugins/animation.blade.php`                           |

### 3.5 Auth & Error (3 estilos: Basic, Card, Split)

**9 páginas × 3 estilos = 27 páginas de auth** ✅

Páginas: `sign-in`, `sign-up`, `reset-pass`, `new-pass`, `two-factor`, `lock-screen`, `success-mail`, `login-pin`, `delete-account`.

> **Observação:** Spec do prompt lista 11 páginas, mas as views reais são 9 por estilo. Os itens "Reset Password" e "New Password" do spec são os mesmos `reset-pass` e `new-pass`, e o spec conta "Sign In" separado dos estilos. Alinhado: **9 páginas distintas de auth por estilo**.

**Erros (sem variação de estilo):** `400`, `401`, `403`, `404`, `408`, `500`, `maintenance` ✅

### 3.6 Charts

**ApexCharts (20 tipos):** ✅
area, bar, boxplot, bubble, candlestick, column, funnel, heatmap, line, mixed, pie, polar-area, radar, radialbar, range, scatter, slope, sparklines, timeline, treemap

**ECharts (11 tipos — spec diz 10):** ✅
area, bar, candlestick, gauge, geo-map, heatmap, line, `other`, pie, radar, scatter

> `other.blade.php` agrupa demos variados (mapas de árvore, etc.).

---

## 4. Plugins JS/CSS — Lista Canônica

### Plugins CONFIRMADOS (encontrados em package.json + Docs oficiais)

| Biblioteca                             | Versão          | Uso                                                                                                                                                | Categoria   |
| -------------------------------------- | --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| **preline**                            | 4.0.1           | Dropdowns, modals, offcanvas, tabs, tooltips, popovers, accordions, stepper, collapse, select, input-file, toggle-password, scrollspy, copy-markup | **Base UI** |
| tailwindcss                            | 4.1.18          | CSS base                                                                                                                                           | Core        |
| @tailwindcss/forms                     | 0.5.11          | Reset de forms                                                                                                                                     | Core        |
| @tailwindcss/typography                | 0.5.19          | Prose                                                                                                                                              | Core        |
| @iconify/json + @iconify/tailwind4     | 2.2.384 / 1.0.6 | Ícones (Tabler + Lucide + Flags)                                                                                                                   | Icons       |
| lucide                                 | 0.542.0         | Ícones Lucide via JS (alternativa ao iconify)                                                                                                      | Icons       |
| apexcharts                             | 5.3.5           | 20 tipos de gráficos                                                                                                                               | Charts      |
| echarts                                | 6.0.0           | 11 tipos de gráficos (alternativa ao Apex)                                                                                                         | Charts      |
| chart.js                               | 4.5.1           | Backup/alternativa (uso limitado)                                                                                                                  | Charts      |
| jsvectormap                            | 1.7.0           | Mapas vetoriais (world, países)                                                                                                                    | Maps        |
| leaflet                                | 1.9.4           | Mapas interativos                                                                                                                                  | Maps        |
| flatpickr                              | 4.6.13          | Datepicker                                                                                                                                         | Forms       |
| daterangepicker                        | 3.1.0           | Date range picker                                                                                                                                  | Forms       |
| dayjs                                  | 1.11.19         | Manipulação de datas (moderno)                                                                                                                     | Utility     |
| moment                                 | 2.30.1          | Manipulação de datas (legado — daterangepicker)                                                                                                    | Utility     |
| choices.js                             | 11.1.0          | Select searchable                                                                                                                                  | Forms       |
| select2                                | 4.1.0-rc.0      | Select avançado (legado, jQuery-based)                                                                                                             | Forms       |
| inputmask                              | 5.0.9           | Máscara de input                                                                                                                                   | Forms       |
| typeahead.js                           | 0.11.1          | Autocomplete                                                                                                                                       | Forms       |
| nouislider                             | 15.8.1          | Range slider                                                                                                                                       | Forms       |
| @simonwep/pickr                        | 1.9.1           | Colorpicker                                                                                                                                        | Forms       |
| dropzone                               | 6.0.0-beta.2    | File upload drag-and-drop                                                                                                                          | Forms       |
| filepond                               | 4.32.8          | File upload moderno (+ 4 plugins)                                                                                                                  | Forms       |
| filepond-plugin-file-encode            | 2.1.14          | Base64 encoding                                                                                                                                    | Forms       |
| filepond-plugin-file-validate-size     | 2.2.8           | Validação tamanho                                                                                                                                  | Forms       |
| filepond-plugin-image-exif-orientation | 1.0.11          | Rotação EXIF                                                                                                                                       | Forms       |
| filepond-plugin-image-preview          | 4.6.12          | Preview imagem                                                                                                                                     | Forms       |
| quill                                  | 2.0.3           | Rich text editor                                                                                                                                   | Forms       |
| datatables.net-dt                      | 2.3.3           | DataTables core (jQuery)                                                                                                                           | Tables      |
| datatables.net-buttons-dt              | 3.2.5           | Export (CSV/Excel/PDF/Print)                                                                                                                       | Tables      |
| datatables.net-fixedcolumns-dt         | 5.0.4           | Colunas fixas                                                                                                                                      | Tables      |
| datatables.net-fixedheader-dt          | 4.0.3           | Cabeçalho fixo                                                                                                                                     | Tables      |
| datatables.net-keytable-dt             | 2.12.1          | Navegação por teclado                                                                                                                              | Tables      |
| datatables.net-responsive-dt           | 3.0.6           | Responsivo                                                                                                                                         | Tables      |
| datatables.net-select-dt               | 3.1.0           | Seleção de linhas                                                                                                                                  | Tables      |
| jszip                                  | 3.10.1          | Suporte a export .xlsx                                                                                                                             | Tables      |
| pdfmake                                | 0.2.20          | Suporte a export PDF (DataTables)                                                                                                                  | Tables      |
| pdfjs-dist                             | 2.16.105        | Visualizar PDF                                                                                                                                     | Viewer      |
| fullcalendar                           | 6.1.19          | Calendário                                                                                                                                         | Apps        |
| sortablejs                             | 1.15.6          | Drag-and-drop de listas                                                                                                                            | Plugins     |
| muuri                                  | 0.9.5           | Grid drag-and-drop                                                                                                                                 | Plugins     |
| masonry-layout                         | 4.2.2           | Layout masonry                                                                                                                                     | Plugins     |
| glightbox                              | 3.3.1           | Lightbox de imagens                                                                                                                                | Plugins     |
| plyr                                   | 3.8.4           | Player de vídeo                                                                                                                                    | Plugins     |
| clipboard                              | 2.0.11          | Copiar para clipboard                                                                                                                              | Plugins     |
| sweetalert2                            | 11.22.5         | Modais bonitos                                                                                                                                     | Feedback    |
| animate.css                            | 4.1.1           | Animações CSS                                                                                                                                      | Plugins     |
| @sjmc11/tourguidejs                    | 0.0.27          | Tour/onboarding                                                                                                                                    | Plugins     |
| ladda                                  | 1.0.6           | Loading buttons                                                                                                                                    | Plugins     |
| jstree                                 | 3.3.17          | Tree view                                                                                                                                          | Plugins     |
| tinycon                                | 0.6.8           | Live favicon                                                                                                                                       | Plugins     |
| diff                                   | 8.0.3           | Text diff                                                                                                                                          | Plugins     |
| simplebar                              | 6.3.3           | Scrollbar customizada                                                                                                                              | Plugins     |
| prismjs                                | 1.30.0          | Syntax highlighting de código                                                                                                                      | Plugins     |
| jquery                                 | 3.7.1           | Dependência de DataTables/Select2/Typeahead/Daterangepicker                                                                                        | Legacy      |

### Plugins REMOVIDOS / NÃO encontrados

| Item da spec               |                                            Status                                            |
| -------------------------- | :------------------------------------------------------------------------------------------: |
| TanStack Tables            |              ❌ Só existe nas variantes React/Angular/Next, **não na Laravel**.              |
| Tagify (select)            |                                 ❌ Não está no package.json.                                 |
| Summernote (editor)        |                              ❌ Removido — só Quill disponível.                              |
| Touchspin                  |                           ❌ Não encontrado como plugin dedicado.                            |
| Google Maps                | ⚠️ `maps/google.blade.php` existe mas **não há SDK** no package.json (provável stub/iframe). |
| 3º Dashboard (ex.: "saas") |                      ❌ Só há 2 dashboards: `analytics` + `ecommerce`.                       |
| Landing Page               |                            ❌ Não encontrado `landing.blade.php`.                            |
| SCSS Variables             |             ❌ Versão Tailwind usa CSS custom properties (`/config/_root.css`).              |

---

## 5. Skin Recomendada para o Portal ArtFinal

O Inspinia v5.0 oferece **11 skins**:

```
default, minimal, modern, material, saas, flat,
galaxy, luxe, retro, neon, pixel
```

### Recomendação: **Skin `default`** para o Admin

**Justificativa:**

1. É a skin sobre a qual todas as screenshots do PRD v3.1 foram pensadas (linhagem direta do Inspinia clássico)
2. Maior cobertura de componentes testados — os outros skins herdam do default e sobrescrevem
3. Paleta neutra compatível com a identidade "ArtFinal" (sem forçar cores de SaaS startup)
4. Dark mode estável
5. Compatibilidade futura: skins decorativas (galaxy/luxe/retro/neon/pixel) são exóticas e podem ser removidas em versões futuras

### Configuração sugerida (`defaultConfig`)

```js
{
  dir: 'ltr',
  skin: 'default',
  theme: 'light',           // usuário pode alternar
  width: 'fluid',           // máximo aproveitamento em desktop 1366×768
  position: 'fixed',        // topbar + sidenav fixos
  orientation: 'vertical',
  'sidenav-size': 'default',
  'sidenav-user': true,     // mostrar card do usuário logado
  'topbar-color': 'light',
  'sidenav-color': 'dark'   // contraste com conteúdo claro
}
```

---

## 6. Discrepâncias entre Specs do Prompt e Realidade

Itens onde o prompt `PROMPT-ANALISE-INSPINIA.md` usa informações desatualizadas da página de venda do Inspinia:

| Spec do Prompt          | Realidade v5.0                                               | Ação                                        |
| ----------------------- | ------------------------------------------------------------ | ------------------------------------------- |
| "235+ pages"            | 218 pages oficial / 240 .blade.php (inclui layouts/partials) | Documentar 218 pages reais                  |
| "6 skins"               | 11 skins (+ galaxy, luxe, retro, neon, pixel)                | Ajustar README / doc                        |
| "3 dashboards"          | 2 dashboards (analytics, ecommerce)                          | Remover referência ao 3º                    |
| "SCSS variables"        | CSS custom properties em `_root.css`                         | Adaptar seção de customização               |
| "Built using Bootstrap" | NÃO usa Bootstrap                                            | Remover checklist Bootstrap                 |
| "TanStack Tables"       | Não na variante Laravel                                      | Não documentar                              |
| "Tagify"                | Não instalado                                                | Usar Choices.js no lugar                    |
| "Summernote"            | Removido                                                     | Usar Quill                                  |
| "Input Touchspin"       | Não é plugin dedicado                                        | Usar inputmask ou componente custom         |
| "Google Maps"           | View existe, SDK ausente                                     | Validar antes de usar ou trocar por Leaflet |
| "Tailwind 4.2.x"        | Atualmente 4.1.18                                            | Versão OK (semver permite 4.2)              |
| "W3C Validated"         | Não verificável estaticamente                                | Ignorar critério                            |

---

## 7. Plano de Skin × Sprint do Portal ArtFinal

O mapeamento prático entre sprints do PRD v3.1.0 e itens do Inspinia a componentizar:

| Sprint (PRD) | Fase             | Precisa do Inspinia                                                                                                              |     Onda     |
| :----------: | ---------------- | -------------------------------------------------------------------------------------------------------------------------------- | :----------: |
|     1–3      | Fundação         | Nada visual (só infra/migrations)                                                                                                |      —       |
|     4–9      | Portal Adesão    | **Portal usa skin própria (Preline puro ou Tailwind puro)**. Pode reusar primitivas (input, button, alert, stepper) do Inspinia. |     2–3      |
|    10–11     | Portal Área      | Portal continua com sua identidade.                                                                                              |     2–3      |
|      15      | Admin Auth       | `auth-split/sign-in.blade.php` + layout auth                                                                                     |  **Onda 1**  |
|      16      | Admin Layout     | `layouts/vertical` + sidebar default + topbar light                                                                              |  **Onda 1**  |
|    17–19     | Admin CRUDs base | DataTables + forms (input, select, datepicker) + modals + drawer                                                                 | **Onda 2–3** |
|    20–23     | Admin Financeiro | KPI cards + ApexCharts (bar, line, pie) + DataTables com filtros                                                                 | **Onda 2–4** |
|      24      | Admin Final      | Tree view, timeline, sortable (termos)                                                                                           | **Onda 4–6** |

---

## 8. Ondas de Componentização (ordem de trabalho Fase 2–6)

| Onda | Prioridade | Categoria                                                                                                                                  | Esforço relativo | Sprint-alvo |
| :--: | :--------: | ------------------------------------------------------------------------------------------------------------------------------------------ | :--------------: | :---------: |
|  1   |   **P0**   | Layouts + Navigation (vertical, sidebar default+colors, topbar, breadcrumb, footer, page-header)                                           |      Médio       |    15–16    |
|  2   |   **P1**   | Data + Feedback (KPI card, alert, toast, modal, button, loading button, status badge, empty state, confirm dialog, drawer, tabs)           |       Alto       |    15–19    |
|  3   |   **P2**   | Forms + Tables (inputs, selects, datepickers, masked inputs, file upload, wizard, data-table, filter panel, action dropdown, bulk actions) |    Muito alto    |    17–19    |
|  4   |   **P3**   | Charts + Dashboards (ApexCharts 20 tipos → usar 5–8, ECharts opcional, chart-card wrapper, dashboards de referência)                       |      Médio       |    20–23    |
|  5   |   **P4**   | Pages (auth split, error pages, profile, account-settings, pricing não, FAQ, coming-soon)                                                  |      Médio       |    15–24    |
|  6   |   **P5**   | Plugins (sortable para termos, tree-view para permissões, SweetAlert2, clipboard, tour opcional, animate opcional)                         |      Baixo       |     24+     |

**Ondas 5 e 6 são seletivas** — muitas páginas do Inspinia (ex: E-commerce com 23 telas, Forum, Blog, Pin Board) **não serão usadas** no Portal ArtFinal. A triagem da Fase 5 vai marcar o que descartar.

---

## 9. Índice da Documentação Granular (Fase 2 em diante)

Os arquivos abaixo serão criados durante as Fases 2–6. Esta seção funciona como **mapa do que vai existir**, não como índice de algo que já existe.

```
docs/template/INSPINIA/
│
├── README.md                           ← (este arquivo — Fase 1)
│
├── Layouts/                            ← Fase 2 Onda 1
│   ├── vertical.md
│   ├── horizontal.md
│   ├── boxed.md
│   ├── fluid.md
│   ├── scrollable.md
│   ├── compact.md
│   └── skins.md
│
├── Components/                         ← Fase 2 Onda 2
│   ├── Navigation/
│   │   ├── sidebar.md                  (6 sizes × 5 colors)
│   │   ├── topbar.md                   (4 colors)
│   │   ├── breadcrumb.md
│   │   ├── footer.md
│   │   └── page-title.md
│   ├── UI/
│   │   ├── accordion.md
│   │   ├── alert.md
│   │   ├── badge.md
│   │   ├── button.md
│   │   ├── card.md
│   │   ├── carousel.md
│   │   ├── collapse.md
│   │   ├── dropdown.md
│   │   ├── images.md
│   │   ├── links.md
│   │   ├── list-group.md
│   │   ├── modal.md
│   │   ├── notification.md
│   │   ├── offcanvas.md
│   │   ├── pagination.md
│   │   ├── placeholder.md
│   │   ├── popover.md
│   │   ├── progress.md
│   │   ├── scrollspy.md
│   │   ├── spinner.md
│   │   ├── tab.md
│   │   ├── tooltip.md
│   │   ├── typography.md
│   │   └── utility-colors.md
│   ├── Data/                           ← Derivado dos dashboards
│   │   ├── kpi-card.md
│   │   ├── widget.md
│   │   ├── metric.md
│   │   └── status-badge.md
│   └── Feedback/
│       ├── toast.md
│       ├── sweetalert.md
│       ├── loading-button.md
│       ├── empty-state.md
│       └── confirm-dialog.md
│
├── Forms/                              ← Fase 2 Onda 3
│   ├── elements.md
│   ├── layout.md
│   ├── select-choices.md
│   ├── select-select2.md
│   ├── datepicker-flatpickr.md
│   ├── daterange-picker.md
│   ├── colorpicker-pickr.md
│   ├── file-upload-dropzone.md
│   ├── file-upload-filepond.md
│   ├── input-mask.md
│   ├── range-slider-nouislider.md
│   ├── typeahead.md
│   ├── wizard.md
│   ├── validation.md
│   └── text-editor-quill.md
│
├── Tables/                             ← Fase 2 Onda 3
│   ├── static.md
│   ├── custom.md
│   └── DataTables/
│       ├── basic.md
│       ├── ajax.md
│       ├── export.md
│       ├── select.md
│       ├── javascript.md
│       ├── rendering.md
│       ├── scroll.md
│       ├── fixed-columns.md
│       ├── fixed-header.md
│       ├── columns-show-hide.md
│       ├── column-search.md
│       ├── range-search.md
│       ├── child-rows.md
│       ├── rows-add.md
│       └── checkbox-select.md
│
├── Charts/                             ← Fase 2 Onda 4
│   ├── ApexCharts/
│   │   ├── area.md, bar.md, bubble.md, candlestick.md, column.md
│   │   ├── heatmap.md, line.md, mixed.md, pie.md, radar.md
│   │   ├── radialbar.md, scatter.md, sparklines.md, timeline.md
│   │   ├── boxplot.md, treemap.md, polar-area.md, range.md
│   │   └── funnel.md, slope.md
│   └── ECharts/
│       ├── area.md, bar.md, candlestick.md, gauge.md, geo-map.md
│       ├── heatmap.md, line.md, pie.md, radar.md, scatter.md
│       └── other.md
│
├── Dashboards/                         ← Fase 2 Onda 4
│   ├── analytics.md
│   └── ecommerce.md
│
├── Icons/
│   ├── tabler.md
│   ├── lucide.md
│   └── flags.md
│
├── Maps/
│   ├── leaflet.md
│   ├── vector-jsvectormap.md
│   └── google.md                       (flag: "verificar se existe SDK")
│
├── Pages/                              ← Fase 2 Onda 5
│   ├── Auth/
│   │   ├── sign-in-basic.md, sign-in-card.md, sign-in-split.md
│   │   ├── sign-up-*.md (3 estilos)
│   │   ├── reset-pass-*.md, new-pass-*.md
│   │   ├── two-factor-*.md, lock-screen-*.md
│   │   ├── success-mail-*.md, login-pin-*.md
│   │   └── delete-account-*.md
│   ├── Error/
│   │   ├── 400.md, 401.md, 403.md, 404.md, 408.md, 500.md
│   │   └── maintenance.md
│   ├── Utility/
│   │   ├── profile.md, account-settings.md, faq.md, pricing.md
│   │   ├── timeline.md, search-results.md, coming-soon.md
│   │   ├── terms-conditions.md, privacy-policy.md, sitemap.md
│   │   ├── gallery.md, empty.md
│   └── Apps/
│       (triagem na Fase 5 — muitas não serão usadas)
│
└── Plugins/                            ← Fase 2 Onda 6
    ├── sortable.md, clipboard.md, tree-view.md, tour.md
    ├── animation.md, masonry.md, video-player-plyr.md
    ├── idle-timer.md, live-favicon-tinycon.md, text-diff.md
    ├── pass-meter.md, pdf-viewer.md, sweet-alerts.md
    ├── loading-buttons-ladda.md, i18.md
```

**Contagem estimada de arquivos .md a criar:** ~155 (Layouts 7 + Components 43 + Forms 15 + Tables 17 + Charts 31 + Dashboards 2 + Icons 3 + Maps 3 + Pages Auth 27 + Error 7 + Utility 12 + Apps ~20 após triagem + Plugins 15).

---

## 10. Status da Fase 1

| Item da Fase 1                           |         Status         |
| ---------------------------------------- | :--------------------: |
| Percorrer estrutura do template          |           ✅           |
| Ler documentação oficial                 | ✅ (`Docs/index.html`) |
| Preencher checklist de specs             |      ✅ (seção 3)      |
| Contar totais                            |      ✅ (seção 2)      |
| Identificar versões exatas               |      ✅ (seção 1)      |
| Listar plugins JS                        |      ✅ (seção 4)      |
| Criar `docs/template/INSPINIA/README.md` |   ✅ (este arquivo)    |

**Fase 1 CONCLUÍDA. Aguardando aprovação para iniciar Fase 2.**

---

## Changelog

| Data       | Descrição                                                        |
| ---------- | ---------------------------------------------------------------- |
| 2026-04-11 | Documento criado — Fase 1 do prompt `PROMPT-ANALISE-INSPINIA.md` |
