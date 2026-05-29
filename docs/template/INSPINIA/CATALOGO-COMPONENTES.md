# Catálogo de Componentes Inspinia × Blade

> **Fonte de verdade** do catálogo de componentes derivados do template Inspinia v5.0 Tailwind.
> Toda criação/alteração de UI no admin deve consultar este arquivo **antes** de escrever HTML.
>
> **Princípios:** reuso primeiro, composição segundo, variação por props em terceiro, componente novo em último caso. Páginas completas **não** são componentes.

**Versão:** 1.0.0
**Data:** 2026-04-11

---

## 1. Como ler este catálogo

### 1.1 Legenda — Vai usar

| Símbolo | Significado                                                                                 |
| :-----: | ------------------------------------------------------------------------------------------- |
|   🟢    | Confirmado — fazer sem pedir confirmação                                                    |
|   🟡    | A validar — alinhar antes de usar (há decisão pendente, dependência externa ou ambiguidade) |
|   🔴    | Não usar agora — não adotado no catálogo                                                    |

### 1.2 Legenda — Prioridade

| Prioridade | Quando atacar                                                     |
| :--------: | ----------------------------------------------------------------- |
|   **P0**   | Pré-requisito de tudo. Sem isto nada renderiza (layout, nav).     |
|   **P1**   | Base de UI/Feedback.                                              |
|   **P2**   | Formulários e tabelas.                                            |
|   **P3**   | Charts/dashboards.                                                |
|   **P4**   | Pages (auth, erros, settings).                                    |
|   **P5**   | Plugins pontuais.                                                 |

### 1.3 Legenda — Status de implementação

| Símbolo | Significado                                                                        |
| :-----: | ---------------------------------------------------------------------------------- |
|   🔴    | Não iniciado — nem a doc de referência foi consumida em código                     |
|   🟡    | Em progresso — parcialmente componentizado ou em revisão                           |
|   🟢    | Concluído — componente Blade existe em `resources/views/components/` e está em uso |

### 1.4 Legenda — Decisão arquitetural

| Símbolo | Significado                                                                         |
| :-----: | ----------------------------------------------------------------------------------- |
|   ✅    | Novo componente Blade anônimo (`resources/views/components/...`)                    |
|   ♻️    | Reuso de componente já existente no catálogo (ver coluna "alvo de reuso")           |
|   ➕    | Variação por prop (mesmo arquivo, feature toggle via `@props`)                      |
|   🧩    | Composição de subcomponentes (ex: `tabs` + `tab-nav` + `tab-trigger` + `tab-panel`) |
|   ⚙️    | Helper JS / bridge (sem arquivo `.blade.php` próprio)                               |
|   ❌    | Não componentizar — é view/partial/página (não reutilizável)                        |

### 1.5 Escopo

Este catálogo cobre os **~62 componentes da seção "Vai Usar"** — os itens do Inspinia efetivamente adotados pela aplicação. Os itens não adotados ficam fora do catálogo e são listados de forma resumida na seção 10 deste documento.

---

## 2. Resumo Executivo

| Métrica                                                          |  Valor  |
| ---------------------------------------------------------------- | :-----: |
| Componentes `🟢 Vai usar` com doc em `docs/template/INSPINIA/**` | **67**  |
| Componentes Blade anônimos a criar (resources/views/components)  | **~56** |
| Views (páginas) a criar (não componentizáveis)                   |  **6**  |
| Mixins/embuts (não geram arquivo `.blade.php` próprio)           |  **4**  |
| Itens 🟡 a validar antes do código                               |  **2**  |
| Componentes já criados (status 🟢)                               | **57**  |
| Componentes 🔴 (não iniciados)                                   |  **9**  |

**Convenção de namespaces:**

- `x-admin.*` → exclusivo do backoffice (Inspinia)
- `x-shared.*` → componentes compartilhados

---

## 3. Índice por Categoria

| Seção                                                | Escopo                                    | Prioridade | Itens |
| ---------------------------------------------------- | ----------------------------------------- | :--------: | :---: |
| [4. Layouts & Navigation](#4-layouts--navigation-p0) | Esqueleto da interface admin              |     P0     |   6   |
| [5. UI Base](#5-ui-base-p1)                          | Cards, tabs, modals, alerts, etc.         |     P1     |  16   |
| [6. Feedback](#6-feedback-p1)                        | Toasts, loading, confirm, empty           |     P1     |   4   |
| [7. Data Display](#7-data-display-p1)                | KPI, status-badge                         |     P1     |   2   |
| [8. Forms](#8-forms-p2)                              | Inputs, selects, pickers, uploads, wizard |     P2     |  18   |
| [9. Tables](#9-tables-p2)                            | DataTable, static, custom/timeline        |     P2     |   3   |
| [10. Charts & Dashboards](#10-charts--dashboards-p3) | ApexCharts wrappers + referência          |     P3     |   6   |
| [11. Pages (views)](#11-pages-views-p4)              | Auth, error, account settings             |     P4     |   6   |
| [12. Plugins pontuais](#12-plugins-pontuais-p5)      | Sortable, clipboard, pass-meter           |     P5     |   3   |
| [13. Itens a validar 🟡](#13-itens-a-validar-)       | Decisões pendentes                        |     —      |   3   |
| [14. Não adotados (resumo)](#14-não-adotados-resumo) | Itens não adotados do Inspinia            |     —      |   19  |

---

## 4. Layouts & Navigation (P0)

> **Pré-requisito absoluto.** Sem isto nada do admin renderiza.

|  #  | Blade destino                              | Categoria      | Doc                                                                      | Vai usar | Prioridade | Status |                       Decisão                        |
| :-: | ------------------------------------------ | -------------- | ------------------------------------------------------------------------ | :------: | :--------: | :----: | :--------------------------------------------------: |
| 01  | `admin/layouts/app.blade.php`              | Layout         | [vertical.md](template/INSPINIA/Layouts/vertical.md)                     |    🟢    |     P0     |   🟢   |          🧩 (adapter do `<x-admin.layout>`)          |
| 02  | `admin/partials/theme-bootstrap.blade.php` | Layout/partial | [skins.md](template/INSPINIA/Layouts/skins.md)                           |    🟢    |     P0     |   🟢   | 🧩 (adapter do `<x-admin.partials.theme-bootstrap>`) |
| 03  | `x-admin.sidebar`                          | Navigation     | [sidebar.md](template/INSPINIA/Components/Navigation/sidebar.md)         |    🟢    |     P0     |   🟢   |                          ✅                          |
| 04  | `x-admin.topbar`                           | Navigation     | [topbar.md](template/INSPINIA/Components/Navigation/topbar.md)           |    🟢    |     P0     |   🟢   |          🧩 (compõe dropdown, badge, notif)          |
| 05  | `x-admin.footer`                           | Navigation     | [footer.md](template/INSPINIA/Components/Navigation/footer.md)           |    🟢    |     P0     |   🟢   |                          ✅                          |
| 06  | `x-admin.page-header`                      | Navigation     | [page-header.md](template/INSPINIA/Components/Navigation/page-header.md) |    🟢    |     P0     |   🟢   |                🧩 (compõe breadcrumb)                |

**Notas:**

- `app.blade.php` e `theme-bootstrap.blade.php` não são componentes — são **view/partial** consumidos via `@extends` / `@include`. Ainda assim entram no catálogo por serem referenciados pela doc.
- `topbar` compõe `dropdown`, `badge` (notificações) e `button` — Batch 2 concluído em 2026-04-11 com essa composição efetivamente aplicada.
- Fonte da verdade do shell admin: `<x-admin.layout>` + `<x-admin.partials.theme-bootstrap>`. Os arquivos em `admin/layouts/*` e `admin/partials/*` existem como adapters de compatibilidade.
- `x-admin.mega-menu` não entra no catálogo: o recurso foi removido do escopo oficial do topbar.
- `x-admin.notification-bell` também não entra como componente autônomo nesta fase: o sino/notificações permanece composição interna do `x-admin.topbar`.

---

Batch 1 concluído em 2026-04-11: `alert`, `badge`, `button`, `card`, `breadcrumb`, `dropdown`, `drawer`, `collapse`.
Batch 2 concluído em 2026-04-11: `layout`, `theme-bootstrap`, `sidebar`, `topbar`, `page-header`, `footer`.

## 5. UI Base (P1)

> Alicerce visual. Todo form/tabela/modal do sistema depende destes.

|  #  | Blade destino           | Categoria      | Doc                                                            | Vai usar | Prioridade | Status |                              Decisão                               |
| :-: | ----------------------- | -------------- | -------------------------------------------------------------- | :------: | :--------: | :----: | :----------------------------------------------------------------: |
| 07  | `x-shared.card`         | UI             | [card.md](template/INSPINIA/Components/UI/card.md)             |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 08  | `x-shared.alert`        | UI             | [alert.md](template/INSPINIA/Components/UI/alert.md)           |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 09  | `x-shared.badge`        | UI             | [badge.md](template/INSPINIA/Components/UI/badge.md)           |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 10  | `x-shared.progress-bar` | UI             | [progress.md](template/INSPINIA/Components/UI/progress.md)     |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 11  | `x-shared.modal`        | UI             | [modal.md](template/INSPINIA/Components/UI/modal.md)           |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 12  | `x-admin.drawer`        | UI (offcanvas) | [drawer.md](template/INSPINIA/Components/UI/drawer.md)         |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 13  | `x-shared.button`       | UI             | [button.md](template/INSPINIA/Components/UI/button.md)         |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 14  | `x-shared.tooltip`      | UI             | [tooltip.md](template/INSPINIA/Components/UI/tooltip.md)       |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 15  | `x-shared.dropdown`     | UI             | [dropdown.md](template/INSPINIA/Components/UI/dropdown.md)     |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 16  | `x-shared.accordion`    | UI             | [accordion.md](template/INSPINIA/Components/UI/accordion.md)   |    🟢    |     P1     |   🟢   |                      🧩 (+ `accordion-item`)                       |
| 17  | `x-shared.collapse`     | UI             | [collapse.md](template/INSPINIA/Components/UI/collapse.md)     |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 18  | `x-shared.list-group`   | UI             | [list-group.md](template/INSPINIA/Components/UI/list-group.md) |    🟢    |     P1     |   🟢   |                      🧩 (+ `list-group-item`)                      |
| 19  | `x-shared.breadcrumb`   | UI             | [breadcrumb.md](template/INSPINIA/Components/UI/breadcrumb.md) |    🟢    |     P1     |   🟢   |                                 ✅                                 |
| 20  | `x-shared.pagination`   | UI             | [pagination.md](template/INSPINIA/Components/UI/pagination.md) |    🟢    |     P1     |   🟢   | ✅ (tema `vendor.pagination.*` + registro no `AppServiceProvider`) |
| 21  | `x-shared.tabs`         | UI             | [tabs.md](template/INSPINIA/Components/UI/tabs.md)             |    🟢    |     P1     |   🟢   |        🧩 (família `tab-nav` + `tab-trigger` + `tab-panel`)        |
| 22  | `x-shared.spinner`      | UI             | [spinner.md](template/INSPINIA/Components/UI/spinner.md)       |    🟢    |     P1     |   🟢   |                                 ✅                                 |

**Notas:**

- `drawer` é **admin-only**: é o offcanvas lateral usado no shell do backoffice.
- `tooltip` foi oficializado em 2026-04-12 como `x-shared.tooltip` para conteúdo textual simples; conteúdo rico continua fora do escopo e não vira popover nesta fase.
- `tabs`, `accordion` e `list-group` são **composições** (componente pai + item) — gerar pelo menos 2 arquivos cada.
- `tabs` fica oficializado como família `x-shared.*`: `x-shared.tabs` continua sendo o nome guarda-chuva no catálogo/mapa, mas a API Blade final é a composição `x-shared.tab-nav` + `x-shared.tab-trigger` + `x-shared.tab-panel`. O wrapper array-driven foi descartado.
- `pagination` foi concluída como **tema do paginator do Laravel**, não como componente Blade clássico: os arquivos finais ficam em `resources/views/vendor/pagination/` e são registrados globalmente no `AppServiceProvider`.
- **Batch 1 concluído em 2026-04-11:** `alert`, `badge`, `button`, `card`, `breadcrumb`, `dropdown`, `drawer` e `collapse` já têm implementação real + preview visual.
- **Batch 3 concluído em 2026-04-11:** `toast`, `empty-state`, `loading-button`, `status-badge`, `tabs` e `confirm-dialog` já têm implementação real/helper + preview visual.
- **Batch 4 concluído em 2026-04-11:** `input`, `textarea`, `select`, `checkbox`, `radio`, `toggle`, `password-input` e `date-picker` já têm implementação real + preview visual.

---

## 6. Feedback (P1)

|  #  | Blade destino             | Categoria | Doc                                                                          | Vai usar | Prioridade | Status |                         Decisão                         |
| :-: | ------------------------- | --------- | ---------------------------------------------------------------------------- | :------: | :--------: | :----: | :-----------------------------------------------------: |
| 23  | `x-shared.toast`          | Feedback  | [toast.md](template/INSPINIA/Components/Feedback/toast.md)                   |    🟢    |     P1     |   🟢   |    🧩 (+ `toast-container` + helper JS de dispatch)     |
| 24  | `x-shared.loading-button` | Feedback  | [loading-button.md](template/INSPINIA/Components/Feedback/loading-button.md) |    🟢    |     P1     |   🟢   |       ➕ (variação de `button` + `wire:loading`)        |
| 25  | `x-shared.confirm-dialog` | Feedback  | [confirm-dialog.md](template/INSPINIA/Components/Feedback/confirm-dialog.md) |    🟢    |     P1     |   🟢   | ⚙️ (helper JS SweetAlert2 + bridge Livewire; sem Blade) |
| 26  | `x-shared.empty-state`    | Feedback  | [empty-state.md](template/INSPINIA/Components/Feedback/empty-state.md)       |    🟢    |     P1     |   🟢   |                           ✅                            |

**Notas:**

- `toast` fica oficializado como família `x-shared.toast` + `x-shared.toast-container`, com fila controlada por helper JS leve e browser events. Não depende de Alpine complexo.
- `loading-button` **não** usa Ladda (Inspinia) — abandonamos em favor de Livewire nativo `wire:loading.attr="disabled"` + `wire:target="..."`. É por isso que é ➕ (variação) e não ✅ (componente novo) — será um wrapper fino sobre a base já consolidada de `x-shared.button`.
- `confirm-dialog` é helper JS do SweetAlert2 exposto via `window.confirmDestructive()` / `window.confirmInfo()` + bridge Livewire. Não existe arquivo Blade próprio para ele.
- `x-admin.confirm-modal` não entra no catálogo oficial: confirmações simples usam `x-shared.confirm-dialog`; `x-shared.modal` fica reservado para conteúdo contextual/rico.

---

## 7. Data Display (P1)

|  #  | Blade destino           | Categoria | Doc                                                                  | Vai usar | Prioridade | Status |             Decisão             |
| :-: | ----------------------- | --------- | -------------------------------------------------------------------- | :------: | :--------: | :----: | :-----------------------------: |
| 27  | `x-admin.kpi-card`      | Data      | [kpi-card.md](template/INSPINIA/Components/Data/kpi-card.md)         |    🟢    |     P1     |   🟢   |               ✅                |
| 28  | `x-shared.status-badge` | Data      | [status-badge.md](template/INSPINIA/Components/Data/status-badge.md) |    🟢    |     P1     |   🟢   | ➕ (wrapper Enum sobre `badge`) |

**Notas:**

- `status-badge` recebe um `BackedEnum` com métodos `label()`, `color()` e (opcional) `icon()`. É **enum-driven** — chama `x-shared.badge` internamente com os valores do Enum. Qualquer Enum de status do projeto deve implementar essa interface.
- Apesar de estar na categoria "Data Display", `x-shared.status-badge` entra no Batch 3 por ser o wrapper oficial de estados do sistema.
- `kpi-card` é admin-only: é o card de indicadores usado nos dashboards do backoffice.

---

## 8. Forms (P2)

> Base de todo cadastro. Integração obrigatória com `@error($name)` do Laravel + Livewire `wire:model`.

### 8.1 Inputs e seleção

|  #  | Blade destino             | Categoria | Doc                                                            | Vai usar | Prioridade | Status |                        Decisão                         |
| :-: | ------------------------- | --------- | -------------------------------------------------------------- | :------: | :--------: | :----: | :----------------------------------------------------: |
| 29  | `x-shared.input`          | Form      | [input.md](template/INSPINIA/Forms/input.md)                   |    🟢    |     P2     |   🟢   |                           ✅                           |
| 30  | `x-shared.textarea`       | Form      | [textarea.md](template/INSPINIA/Forms/textarea.md)             |    🟢    |     P2     |   🟢   |                           ✅                           |
| 31  | `x-shared.select`         | Form      | [select.md](template/INSPINIA/Forms/select.md)                 |    🟢    |     P2     |   🟢   |                           ✅                           |
| 32  | `x-shared.select-search`  | Form      | [select-search.md](template/INSPINIA/Forms/select-search.md)   |    🟢    |     P2     |   🟢   | ✅ (Choices.js wrapper; single/multiple/search/groups) |
| 32a | `x-shared.tags-input`     | Form      | [tags-input.md](template/INSPINIA/Forms/tags-input.md)         |    🟢    |     P2     |   🟢   |      🧩 (wrapper semântico sobre `select-search`)      |
| 33  | `x-shared.checkbox`       | Form      | [checkbox.md](template/INSPINIA/Forms/checkbox.md)             |    🟢    |     P2     |   🟢   |                           ✅                           |
| 34  | `x-shared.radio`          | Form      | [radio.md](template/INSPINIA/Forms/radio.md)                   |    🟢    |     P2     |   🟢   |     🧩 (`x-shared.radio` + `x-shared.radio-group`)     |
| 35  | `x-shared.toggle`         | Form      | [toggle.md](template/INSPINIA/Forms/toggle.md)                 |    🟢    |     P2     |   🟢   |                           ✅                           |
| 36  | `x-shared.password-input` | Form      | [password-input.md](template/INSPINIA/Forms/password-input.md) |    🟢    |     P2     |   🟢   | 🧩 (helper JS de visibilidade + opcional `pass-meter`) |

### 8.2 Datas

|  #  | Blade destino                | Categoria | Doc                                                                  | Vai usar | Prioridade | Status |                        Decisão                        |
| :-: | ---------------------------- | --------- | -------------------------------------------------------------------- | :------: | :--------: | :----: | :---------------------------------------------------: |
| 37  | `x-shared.date-picker`       | Form      | [date-picker.md](template/INSPINIA/Forms/date-picker.md)             |    🟢    |     P2     |   🟢   |        ✅ (Flatpickr wrapper + helper JS leve)        |
| 38  | `x-shared.date-range-picker` | Form      | [date-range-picker.md](template/INSPINIA/Forms/date-range-picker.md) |    🟢    |     P2     |   🟢   | ✅ (componente irmão do `date-picker`, mesma base JS) |

### 8.3 Masked inputs (pt-BR)

|  #  | Blade destino          | Categoria | Doc                                                      | Vai usar | Prioridade | Status |                  Decisão                  |
| :-: | ---------------------- | --------- | -------------------------------------------------------- | :------: | :--------: | :----: | :---------------------------------------: |
| 39  | `x-shared.cpf-input`   | Form      | [cpf-input.md](template/INSPINIA/Forms/cpf-input.md)     |    🟢    |     P2     |   🟢   |        ➕ (variação com Inputmask)        |
| 40  | `x-shared.cnpj-input`  | Form      | [cnpj-input.md](template/INSPINIA/Forms/cnpj-input.md)   |    🟢    |     P2     |   🟢   |                    ➕                     |
| 41  | `x-shared.cep-input`   | Form      | [cep-input.md](template/INSPINIA/Forms/cep-input.md)     |    🟢    |     P2     |   🟢   |     🧩 (Inputmask + ViaCEP helper JS)     |
| 42  | `x-shared.phone-input` | Form      | [phone-input.md](template/INSPINIA/Forms/phone-input.md) |    🟢    |     P2     |   🟢   |                    ➕                     |
| 43  | `x-shared.money-input` | Form      | [money-input.md](template/INSPINIA/Forms/money-input.md) |    🟢    |     P2     |   🟢   | 🧩 (Inputmask + `MoneyHelper::toCents()`) |

### 8.4 Uploads, validação e wizard

|  #  | Blade destino                          | Categoria | Doc                                                      | Vai usar | Prioridade | Status |                  Decisão                   |
| :-: | -------------------------------------- | --------- | -------------------------------------------------------- | :------: | :--------: | :----: | :----------------------------------------: |
| 44  | `x-shared.file-upload`                 | Form      | [file-upload.md](template/INSPINIA/Forms/file-upload.md) |    🟢    |     P2     |   🟢   |     ✅ (modes `livewire` e `dropzone`)     |
| 45  | — (mixin `@error` em `x-shared.input`) | Form      | [validation.md](template/INSPINIA/Forms/validation.md)   |    🟢    |     P2     |   🔴   |                 ❌ (mixin)                 |
| 46  | `x-shared.wizard`                      | Form      | [wizard.md](template/INSPINIA/Forms/wizard.md)           |    🟢    |     P2     |   🔴   | 🧩 (stepper server-driven + `wizard-step`) |

**Notas de arquitetura:**

- Todos os forms devem ler `$errors->has($name)` e propagar `aria-invalid` automaticamente.
- `select-search` e `tags-input` coexistem: o primeiro é genérico/searchable; o segundo é o wrapper semântico para chips/tags.
- `date-picker` e `date-range-picker` coexistem como componentes irmãos e compartilham a mesma base JS do Flatpickr.
- `money-input` **nunca** deve entregar float ao backend — sempre converter para `int` centavos via `MoneyHelper::toCents()`.
- `cep-input` faz fetch ViaCEP no `@blur` e dispara `$dispatch('cep-filled', {logradouro, bairro, cidade, uf})`.
- `wizard` é um stepper multi-etapas server-driven; no admin o padrão preferido para navegação por seções é `tabs`.
- **Batch 5 concluído em 2026-04-12:** `select-search`, `tags-input`, `date-range-picker`, `cpf-input`, `cnpj-input`, `phone-input`, `money-input`, `cep-input` e `file-upload` já têm implementação real + preview visual.

---

## 9. Tables (P2)

|  #  | Blade destino            | Categoria | Doc                                                         | Vai usar | Prioridade | Status |                                                      Decisão                                                      |
| :-: | ------------------------ | --------- | ----------------------------------------------------------- | :------: | :--------: | :----: | :---------------------------------------------------------------------------------------------------------------: |
| 47  | `x-admin.data-table`     | Table     | [data-table.md](template/INSPINIA/Tables/data-table.md)     |    🟢    |     P2     |   🟢   | ➕ (unificado com toggles por prop: `:searchable`, `:exportable`, `:selectable`, `:column-search`, `:date-range`) |
| 48  | `x-shared.static-table`  | Table     | [static-table.md](template/INSPINIA/Tables/static-table.md) |    🟢    |     P2     |   🟢   |                                                        ✅                                                         |
| 49  | `x-admin.timeline-table` | Table     | [custom-table.md](template/INSPINIA/Tables/custom-table.md) |    🟢    |     P2     |   🟢   |                                                        ✅                                                         |

**Decisão crítica registrada:** o Inspinia tem ~11 variantes de DataTables em arquivos separados; a aplicação usa **um único componente com props** para habilitar/desabilitar cada feature. As variantes sobrantes (ajax, child-rows, fixed-columns, fixed-header, rendering custom, etc.) não foram adotadas e podem ser promovidas conforme o volume de dados escalar.

- **Escopo oficial do Batch 6 (2026-04-12):** entram para implementação real `x-admin.data-table` e a composição `x-shared.list-group` + `x-shared.list-group-item`. `filter-panel`, `action-dropdown`, `bulk-actions` e `export-buttons` **não** existem como componentes autônomos nas fontes oficiais: filtros avançados seguem composição com `x-admin.drawer` + forms base; ações por linha seguem `x-shared.dropdown`; exportação e seleção em massa permanecem capacidades do próprio `x-admin.data-table` via props/slots.
- Não existe `x-admin.timeline-item` no catálogo oficial. O reuso de timeline fica limitado a `x-admin.timeline-table`; a timeline visual da aba de histórico continua sendo referência de view, não componente genérico.
- Rodada de fechamento dos remanescentes base concluída em 2026-04-12: `x-shared.pagination`, `x-shared.spinner`, `x-shared.static-table` e `x-admin.timeline-table` agora têm implementação real + preview visual.

---

## 10. Charts & Dashboards (P3)

|  #  | Blade destino                          | Categoria          | Doc                                                        | Vai usar | Prioridade | Status |                    Decisão                    |
| :-: | -------------------------------------- | ------------------ | ---------------------------------------------------------- | :------: | :--------: | :----: | :-------------------------------------------: |
| 50  | `x-admin.chart-card`                   | Chart wrapper      | [chart-card.md](template/INSPINIA/Charts/chart-card.md)    |    🟢    |     P3     |   🟢   |  ✅ (moldura + slot para chart/placeholder)   |
| 51  | `x-admin.chart-bar`                    | Chart (ApexCharts) | [bar.md](template/INSPINIA/Charts/ApexCharts/bar.md)       |    🟢    |     P3     |   🟢   | 🧩 (compõe `chart-card` + bridge `charts.js`) |
| 52  | `x-admin.chart-line`                   | Chart (ApexCharts) | [line.md](template/INSPINIA/Charts/ApexCharts/line.md)     |    🟢    |     P3     |   🟢   |                      🧩                       |
| 53  | `x-admin.chart-column`                 | Chart (ApexCharts) | [column.md](template/INSPINIA/Charts/ApexCharts/column.md) |    🟢    |     P3     |   🟢   |                      🧩                       |
| 54  | `x-admin.chart-pie`                    | Chart (ApexCharts) | [pie.md](template/INSPINIA/Charts/ApexCharts/pie.md)       |    🟢    |     P3     |   🟢   |                      🧩                       |
| 55  | (view dashboard — não-componente)      | Dashboard          | [analytics.md](template/INSPINIA/Dashboards/analytics.md)  |    🟢    |     P3     |   🔴   |                   ❌ (view)                   |

**Notas:**

- Os 4 charts compartilham uma **bridge JS** (`resources/js/admin/charts-bridge.js`) que escuta `Livewire.on('chart-update', …)` para atualizar datasets sem rebuild do DOM. Ver [chart-card.md](template/INSPINIA/Charts/chart-card.md) para o contrato.
- ApexCharts é a biblioteca oficial — ECharts não foi adotado (ficaria reservado para geo-maps).
- **Escopo oficial do Batch 7 (2026-04-12):** entram para implementação real `x-admin.kpi-card`, `x-admin.chart-card` e `x-shared.progress-bar`. `metric` e `widget` ficam fora do escopo oficial por não terem doc detalhada/catálogo próprios; por enquanto permanecem resolvidos por composição com `card`, `list-group`, `data-table` e views específicas.
- Rodada final concluída em 2026-04-12: `x-admin.chart-bar`, `x-admin.chart-line`, `x-admin.chart-column` e `x-admin.chart-pie` agora compõem `x-admin.chart-card` sobre uma bridge única em `resources/js/admin/charts.js`.

---

## 11. Pages (views) (P4)

> **Não são componentes.** São views completas. Entram no catálogo porque a doc de referência cobriu cada uma.

|  #  | View destino                       | Categoria    | Doc                                                                        | Vai usar | Prioridade | Status |        Decisão         |
| :-: | ---------------------------------- | ------------ | -------------------------------------------------------------------------- | :------: | :--------: | :----: | :--------------------: |
| 56  | `admin/auth/login.blade.php`       | Page/Auth    | [sign-in-split.md](template/INSPINIA/Pages/Auth/sign-in-split.md)          |    🟢    |     P4     |   🟢   | 🧩 (`x-admin.auth-form-card`) |
| 57  | `errors/404.blade.php`             | Page/Error   | [404.md](template/INSPINIA/Pages/Error/404.md)                             |    🟢    |     P4     |   🔴   |       ❌ (view)        |
| 58  | `errors/500.blade.php`             | Page/Error   | [500.md](template/INSPINIA/Pages/Error/500.md)                             |    🟢    |     P4     |   🔴   |       ❌ (view)        |
| 59  | `errors/403.blade.php`             | Page/Error   | [403.md](template/INSPINIA/Pages/Error/403.md)                             |    🟢    |     P4     |   🔴   |       ❌ (view)        |
| 60  | `errors/503.blade.php`             | Page/Error   | [maintenance.md](template/INSPINIA/Pages/Error/maintenance.md)             |    🟢    |     P4     |   🔴   |       ❌ (view)        |
| 61  | `admin/account/settings.blade.php` | Page/Utility | [account-settings.md](template/INSPINIA/Pages/Utility/account-settings.md) |    🟢    |     P4     |   🔴   | ❌ (view, 3 sub-rotas) |

**Notas:**

- `account-settings` tem 3 sub-rotas (`perfil`, `senha`, `notificacoes`) com navegação via `x-shared.list-group` — portanto depende de 5, 6 e 7 prontos.
- As páginas de erro usam `<x-admin.partials.theme-bootstrap />` para persistir dark/light via sessionStorage mesmo fora do layout autenticado.
- `admin/auth/login.blade.php` (item 56) usa `<x-admin.auth-form-card />` — componente auth-form-card foi concluído em 2026-05-28. Também utilizado nas páginas forgot-password e reset-password via Livewire.

---

## 12. Plugins pontuais (P5)

|  #  | Blade destino                      | Categoria              | Doc                                                      | Vai usar | Prioridade | Status |                         Decisão                         |
| :-: | ---------------------------------- | ---------------------- | -------------------------------------------------------- | :------: | :--------: | :----: | :-----------------------------------------------------: |
| 62  | `x-admin.sortable-list`            | Plugin (SortableJS)    | [sortable.md](template/INSPINIA/Plugins/sortable.md)     |    🟢    |     P5     |   🟢   |                           ✅                            |
| 63  | `x-shared.copy-button`             | Plugin (clipboard API) | [clipboard.md](template/INSPINIA/Plugins/clipboard.md)   |    🟢    |     P5     |   🟢   |            ✅ (API nativa, sem clipboard.js)            |
| 64  | `x-shared.password-strength-meter` | Plugin (custom Alpine) | [pass-meter.md](template/INSPINIA/Plugins/pass-meter.md) |    🟢    |     P5     |   🟢   | ➕ (subcomponente do `password-input` + uso standalone) |

**Notas:**

- `sortable-list` usa `wire:ignore` e chama `$wire.call(target, ids)` após `onEnd`.
- `copy-button` usa `navigator.clipboard.writeText()` — dispensa `clipboard.js`.
- `password-strength-meter` reutiliza o helper JS dos forms base e também continua acessível via prop `with-meter` do `password-input`.

---

## 13. Itens a validar 🟡

Itens com decisão pendente que **precisam ser discutidos antes** de virar componente.

| Item                  | Por quê é 🟡                                                                        | Decisão a tomar                                                      |
| --------------------- | ----------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| **Editor rich text**  | Inspinia tem Quill mas **não TinyMCE**. TinyMCE self-hosted é grátis, cloud é paga. | Quill (grátis, já alinhado) **ou** TinyMCE self-host (mais features) |
| **Slider numérico**   | noUiSlider não foi adotado; number input atende sem plugin                          | Number input simples **ou** promover noUiSlider                      |

**Ação:** a decisão por cada item deve ser registrada em `docs/02-CONVENTIONS.md` seção "Decisões de UI" **antes** de iniciar o módulo que o consome.

---

## 14. Não adotados (resumo)

> Itens do template Inspinia **não adotados** no catálogo, mas disponíveis para promoção futura. Cada um mantém o caminho original no template.

| Item                      | Caminho original                               | Uso potencial                          | Prioridade futura |
| ------------------------- | ---------------------------------------------- | -------------------------------------- | :---------------: |
| Chat                      | `apps/chat.blade.php`                          | Chat de atendimento                    |      🟢 Alto      |
| Calendar (FullCalendar)   | `apps/calendar.blade.php`                      | Agenda de eventos                      |      🟢 Alto      |
| File Manager              | `apps/file-manager.blade.php`                  | Gestão de documentos                   |      🟢 Alto      |
| Ecommerce Reviews         | `apps/ecommerce/reviews.blade.php`             | Avaliações / reviews                   |      🟢 Alto      |
| Vote List                 | `apps/vote-list.blade.php`                     | Votações, pesquisa de satisfação       |     🟡 Médio      |
| Forum                     | `apps/forum/view.blade.php` + `post.blade.php` | Discussão / fórum                      |     🟡 Médio      |
| Email app (inbox/compose) | `apps/email/*.blade.php`                       | Inbox ticket-like                      |     🟡 Médio      |
| Text Diff                 | `plugins/text-diff.blade.php`                  | Comparar versões de texto              |     🟡 Médio      |
| PDF Viewer                | `plugins/pdf-viewer.blade.php`                 | Preview inline de PDF                  |     🟡 Médio      |
| Tree View (jstree)        | `plugins/tree-view.blade.php`                  | Dados hierárquicos em árvore           |     🟡 Médio      |
| Quill editor              | `form/text-editors.blade.php`                  | Editor rich text (alt TinyMCE)         |     🟡 Médio      |
| noUiSlider                | `form/range-slider.blade.php`                  | Slider numérico                        |     🟡 Médio      |
| Radialbar chart           | `charts/apex/radialbar.blade.php`              | Indicador em formato radial            |     🟡 Médio      |
| Funnel chart              | `charts/apex/funnel.blade.php`                 | Funil de conversão                     |     🟡 Médio      |
| Sparklines chart          | `charts/apex/sparklines.blade.php`             | Mini-gráficos em KPI cards             |     🟡 Médio      |
| Vector map                | `maps/vector.blade.php`                        | Heatmap geográfico                     |     🟡 Médio      |
| TourGuideJS               | `plugins/tour.blade.php`                       | Onboarding de novos usuários           |     🟡 Médio      |
| FilePond                  | `form/fileuploads.blade.php`                   | Alternativa moderna ao Dropzone        |     🔴 Baixo      |
| DataTables child-rows     | `tables/datatables/child-rows.blade.php`       | Linhas expansíveis com detalhe         |     🔴 Baixo      |

---

## 15. Convenções obrigatórias do catálogo

1. **Antes** de escrever HTML em qualquer view/componente, **consultar este catálogo**.
2. Se o componente existe e está 🟢 → usar direto (sem alinhamento).
3. Se está 🟡 → **alinhar com a equipe/doc de decisão** antes de usar.
4. Se está 🔴 (não iniciado) e é necessário → criar o componente **primeiro**, depois consumir. **Nunca** inlinar HTML reutilizável direto na view.
5. Se o componente **não existe** no catálogo → abrir doc em `docs/template/INSPINIA/[Categoria]/<nome>.md`, catalogar aqui, criar o Blade em `resources/views/components/`, **depois** usar.
6. **Ordem de preferência** para nova UI: reuso (♻️) → composição (🧩) → variação por prop (➕) → componente novo (✅).
7. **Dark mode, responsividade e consistência de API** (namespaces `x-admin.*` / `x-shared.*`) são requisitos, não opcionais.
8. Páginas completas **não** são componentes — nunca transformar `<x-admin.dashboard-14-2-page>` ou similar.

---

## 16. Mudanças & Changelog

| Data       | Descrição                                                                                                                                                                                                                        |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026-04-11 | Catálogo criado — 66 itens mapeados a partir do inventário; 4 itens 🟡 a validar; lista de não adotados resumida com 19 itens de maior probabilidade                                                                            |
| 2026-04-12 | Batch 5 concluído: `select-search`, `tags-input`, `date-range-picker`, `cpf-input`, `cnpj-input`, `phone-input`, `money-input`, `cep-input` e `file-upload` promovidos para 🟢; itens 🟡 a validar reduzidos para 3              |
| 2026-04-12 | Batch 6 concluído: `x-admin.data-table` e a composição `x-shared.list-group`/`x-shared.list-group-item` promovidos para 🟢; filtros, bulk actions e export permanecem capacidades/composição, sem componentes autônomos          |
| 2026-04-12 | Batch 7 concluído: `x-admin.kpi-card`, `x-admin.chart-card` e `x-shared.progress-bar` promovidos para 🟢; `metric` e `widget` ficaram oficialmente fora do lote por falta de doc oficial própria |
| 2026-04-12 | Batch 8 concluído: `x-shared.accordion`, `x-shared.modal`, `x-shared.tooltip` e `x-admin.sortable-list` promovidos para 🟢; `offcanvas`, `popover` e `programacao-timeline` não entraram como componentes autônomos oficiais     |
