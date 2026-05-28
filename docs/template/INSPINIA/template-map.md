# Template Inspinia × Componentes Blade — Índice Oficial

> **Documento índice.** Agrega a documentação do template Inspinia v5.0 Tailwind, o catálogo de componentes Blade derivados, o mapa tela → componente e a estratégia de evolução para o portal do formando.
>
> **Leia este documento primeiro** sempre que precisar saber por onde começar.

**Projeto:** Portal ArtFinal — Sistema de Gerenciamento de Formaturas
**Versão:** 2.0.0
**Data:** 2026-04-11

---

## 1. Navegação rápida

| Documento                                                                     | Quando abrir                                                                                                                                                                                        |
| ----------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 📇 [`INSPINIA-CATALOGO-COMPONENTES.md`](INSPINIA-CATALOGO-COMPONENTES.md)     | **Precisa saber se um componente existe.** Lista os ~66 componentes `🟢 Vai usar`, organizados por categoria, com status, prioridade, decisão arquitetural e link para a doc técnica.               |
| 🗺️ [`INSPINIA-MAPA-TELAS-COMPONENTES.md`](INSPINIA-MAPA-TELAS-COMPONENTES.md) | **Vai começar uma tela.** Para cada tela do PRD §14 (+ portal) lista os componentes principais/auxiliares, plugins JS, status e dependências (`🟡 a validar`, `🅿️ parking lot`, `🔌 API-decision`). |
| 📦 [`template/INSPINIA/README.md`](template/INSPINIA/README.md)               | **Quer ver o inventário.** Lista os 240 `.blade.php` do template original, a skin/cores escolhidas, versões de plugins, validação contra a spec.                                                    |
| 🔍 [`template/INSPINIA/TRIAGEM.md`](template/INSPINIA/TRIAGEM.md)             | **Quer entender por quê algo ficou fora.** ~62 "vai usar" + ~95 parking lot + ~30 descartados, com caminho original preservado e cenário futuro de cada item.                                       |
| 📄 `template/INSPINIA/**/<nome>.md`                                           | **Precisa da doc técnica** de um componente específico (código, props, dependências, uso). Cada componente 🟢 tem um arquivo `.md` aqui.                                                            |
| 🧭 [`CLAUDE.md`](../CLAUDE.md) §Componentes Blade                             | **Regras de código** (onde ficam, como nomear, como propagar erros).                                                                                                                                |

---

## 2. Como escolher qual abrir

```
┌──────────────────────────────────────────────────────────────────────┐
│  Vou iniciar um CRUD/tela do admin                                   │
│  └─> INSPINIA-MAPA-TELAS-COMPONENTES.md (seção §14.X)                │
│      └─> para cada componente listado, abrir a doc em                │
│          template/INSPINIA/<Categoria>/<nome>.md                     │
├──────────────────────────────────────────────────────────────────────┤
│  Vou criar um componente novo                                        │
│  └─> 1) verificar se já existe em INSPINIA-CATALOGO-COMPONENTES.md   │
│      2) se não existe, checar em TRIAGEM.md (parking lot pode ter)   │
│      3) se mesmo assim não existe, criar doc em                      │
│         template/INSPINIA/<Categoria>/<nome>.md                      │
│      4) atualizar INSPINIA-CATALOGO-COMPONENTES.md                   │
│      5) criar o .blade.php em resources/views/components/            │
├──────────────────────────────────────────────────────────────────────┤
│  Preciso investigar "existe algo no Inspinia para X?"                │
│  └─> TRIAGEM.md (seção 2 Parking Lot) — busca por texto              │
│      ou README.md (inventário completo)                              │
├──────────────────────────────────────────────────────────────────────┤
│  Estou debugando um componente                                       │
│  └─> template/INSPINIA/<Categoria>/<nome>.md (props, dependências)   │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 3. Estrutura de pastas

### 3.1 Documentação (fonte de verdade)

```
docs/
├── 04-TEMPLATE-MAP-AND-COMPONENTS.md      ← você está aqui (índice)
├── INSPINIA-CATALOGO-COMPONENTES.md        ← catálogo (Fase 3)
├── INSPINIA-MAPA-TELAS-COMPONENTES.md      ← mapa tela → componentes (Fase 3)
└── template/
    └── INSPINIA/
        ├── README.md                       ← inventário do template (Fase 1)
        ├── TRIAGEM.md                      ← triagem + parking lot (Fase 5)
        ├── Layouts/                        ← layouts base (2 arquivos)
        ├── Components/
        │   ├── Navigation/                 ← sidebar/topbar/footer/page-header
        │   ├── UI/                         ← card/tabs/modal/alert/... (16)
        │   ├── Data/                       ← kpi-card, status-badge
        │   └── Feedback/                   ← toast/loading/confirm/empty
        ├── Forms/                          ← inputs/selects/pickers/masks/wizard
        ├── Tables/                         ← data-table/static/timeline
        ├── Charts/
        │   └── ApexCharts/                 ← bar/line/column/pie
        ├── Dashboards/                     ← analytics (referência)
        ├── Pages/
        │   ├── Auth/                       ← sign-in-split
        │   ├── Error/                      ← 404/403/500/maintenance
        │   └── Utility/                    ← account-settings
        └── Plugins/                        ← sortable/clipboard/pass-meter
```

### 3.2 Código (destino)

```
resources/views/components/
├── admin/      ← exclusivo do backoffice (sidebar, topbar, data-table, drawer, kpi-card, sortable-list, chart-*, timeline-table)
├── portal/     ← exclusivo do portal (wizard, formando-selector, parcela-card, section-card)
└── shared/     ← usado por admin e portal (button, input, card, modal, alert, badge, tabs, copy-button, password-input, etc.)
```

---

## 4. Skin/Config oficial do Inspinia

Configuração fixada na [Fase 1](template/INSPINIA/README.md):

| Propriedade     |                                       Valor                                        |
| --------------- | :--------------------------------------------------------------------------------: |
| `skin`          |                                     `default`                                      |
| `sidenav-color` |                                       `dark`                                       |
| `topbar-color`  |                                      `light`                                       |
| `sidenav-size`  |                                     `default`                                      |
| `width`         |                                      `fluid`                                       |
| `position`      |                                      `fixed`                                       |
| `theme`         | persistido via sessionStorage (ver [skins.md](template/INSPINIA/Layouts/skins.md)) |

---

## 5. Resumo da triagem (Fase 5)

| Decisão        | Quantidade | O que é                                                                                         |
| -------------- | :--------: | ----------------------------------------------------------------------------------------------- |
| ✅ Vai usar    |  **~66**   | Documentados em `template/INSPINIA/**/*.md` e catalogados em `INSPINIA-CATALOGO-COMPONENTES.md` |
| 🅿️ Parking Lot |  **~95**   | Preservados em [`TRIAGEM.md`](template/INSPINIA/TRIAGEM.md) §2 com caminho original do template |
| ❌ Descartados |  **~30**   | [`TRIAGEM.md`](template/INSPINIA/TRIAGEM.md) §3 — fora do escopo do domínio                     |
| ⚠️ Ausentes    |   **3**    | TinyMCE, Tagify, Touchspin — alternativas em [`TRIAGEM.md`](template/INSPINIA/TRIAGEM.md) §4    |

**Cobertura:** as 21 telas do admin (§14) + wizards do portal são **100% cobertas** pelos componentes da seção "Vai Usar". 4 itens 🟡 precisam de decisão antes de codar (ver [`INSPINIA-CATALOGO-COMPONENTES.md`](INSPINIA-CATALOGO-COMPONENTES.md) §13).

---

## 6. Fluxo de componentização (resumo)

O processo detalhado está no [`INSPINIA-CATALOGO-COMPONENTES.md`](INSPINIA-CATALOGO-COMPONENTES.md) §15. Abaixo o fluxo curto:

1. **Identificar** — Qual tela? abrir [`INSPINIA-MAPA-TELAS-COMPONENTES.md`](INSPINIA-MAPA-TELAS-COMPONENTES.md)
2. **Checar o catálogo** — componente já existe? está 🟢 (usar), 🟡 (alinhar), ou 🔴 (componentizar primeiro)?
3. **Se 🔴 ou não catalogado** — criar doc técnica em `template/INSPINIA/<Categoria>/<nome>.md`, extrair HTML do Inspinia, limpar, parametrizar, catalogar, **depois** criar `.blade.php` em `resources/views/components/`
4. **Consumir** — nunca inlinar HTML reutilizável em views, sempre passar pelo componente
5. **Ordem de preferência** — reuso (♻️) → composição (🧩) → variação por prop (➕) → componente novo (✅)
6. **Dark mode, responsividade e consistência de API** — requisitos não negociáveis
7. **Páginas completas não são componentes** — nunca transformar `<x-admin.dashboard-14-2-page>` e similares

> **A regra obrigatória completa** está registrada em [`CLAUDE.md`](../CLAUDE.md) §11 e em [`02-CONVENTIONS.md`](02-CONVENTIONS.md) — e aponta de volta para o catálogo + mapa.

---

## 7. Decisões pendentes 🟡

A lista completa está em [`INSPINIA-CATALOGO-COMPONENTES.md`](INSPINIA-CATALOGO-COMPONENTES.md) §13. Resumo:

| Item                                                               | Quando decidir                          |
| ------------------------------------------------------------------ | --------------------------------------- |
| Editor rich text — Quill vs TinyMCE                                | Antes de iniciar 14.11 Gestão de Termos |
| Tags input — resolvido como `x-shared.tags-input` sobre Choices.js | Consolidado em 2026-04-12               |
| Slider de parcelas — number input vs noUiSlider                    | Antes de iniciar 14.14 Simulador        |
| Tooltip — Alpine inline vs componente `x-shared.tooltip`           | Antes de iniciar 14.4 Contratos         |
| Template do portal — Preline UI vs Tailwind puro vs híbrido        | Sprint 4 (início do portal)             |

**Regra:** decisão registrada em [`02-CONVENTIONS.md`](02-CONVENTIONS.md) seção "Decisões de UI" **antes** do início da tela que consome.

---

## 8. Parking Lot — top 10 itens de maior probabilidade

Lista resumida dos itens do Inspinia preservados para futuro (lista completa em [`TRIAGEM.md`](template/INSPINIA/TRIAGEM.md) §2):

| Item                    | Caminho original                   | Cenário futuro                    | Prioridade futura |
| ----------------------- | ---------------------------------- | --------------------------------- | :---------------: |
| Chat                    | `apps/chat.blade.php`              | Atendimento ao formando           |      🟢 Alto      |
| Calendar (FullCalendar) | `apps/calendar.blade.php`          | Agenda de formatura               |      🟢 Alto      |
| File Manager            | `apps/file-manager.blade.php`      | Documentos por formando           |      🟢 Alto      |
| Ecommerce Reviews       | `apps/ecommerce/reviews.blade.php` | Avaliações                        |      🟢 Alto      |
| Vote List               | `apps/vote-list.blade.php`         | Votação de fornecedores/pesquisas |     🟡 Médio      |
| Quill editor            | `form/text-editors.blade.php`      | Alt para TinyMCE (14.11)          |     🟡 Médio      |
| Text Diff               | `plugins/text-diff.blade.php`      | Comparar versões de termos        |     🟡 Médio      |
| PDF Viewer (pdf.js)     | `plugins/pdf-viewer.blade.php`     | Preview inline de termos          |     🟡 Médio      |
| noUiSlider              | `form/range-slider.blade.php`      | Slider de parcelas (14.14)        |     🟡 Médio      |
| Funnel chart            | `charts/apex/funnel.blade.php`     | Funil de conversão do wizard      |     🟡 Médio      |

---

## 9. PARTE LEGADA — Estratégia do Portal do Formando

> Mantida aqui porque a decisão ainda está pendente. Quando o template for escolhido, este conteúdo migra para um documento próprio (`PORTAL-CATALOGO-COMPONENTES.md`).

### 9.1 Contexto

O Portal do Formando é a interface pública, mobile-first, usada por formandos e responsáveis. Tem identidade visual própria, separada do admin. O PRD recomenda Preline UI ou design custom.

### 9.2 Opções em Avaliação

#### Opção A: Preline UI (Tailwind CSS)

Framework open-source Tailwind com componentes prontos de wizard multi-step, pricing cards, dashboard e formulários.

```bash
npm install preline
```

- **Prós:** Wizard nativo (7 etapas da adesão), pricing cards (pacotes), dashboard components, 100% Tailwind, gratuito
- **Contras:** Mais uma dependência, possível conflito de JS com Inspinia se ambos carregarem no mesmo build
- **Mitigação:** Entry points separados no Vite (portal.js e admin.js) eliminam conflito

#### Opção B: Tailwind puro + componentes custom

Design sob medida usando apenas Tailwind CSS 4 e componentes Blade criados do zero ou com a skill frontend-design do Claude.

- **Prós:** Controle total, zero dependência extra, design único para a marca
- **Contras:** Mais tempo de desenvolvimento visual, precisa criar wizard/cards do zero

#### Opção C: Híbrida (Preline como base, customização por cima)

Usar Preline como ponto de partida para os componentes core (wizard, cards) e customizar pesadamente o visual.

### 9.3 Decisão

**Status:** 🟡 Pendente — Definir antes da Sprint 4 (início do desenvolvimento do portal).

A tendência é **Preline UI** ou **Tailwind puro**. A decisão será documentada em [`02-CONVENTIONS.md`](02-CONVENTIONS.md) seção "Decisões de UI" quando tomada.

---

## 10. Changelog

| Data       | Versão | Descrição                                                                                                                                                                                                                                                                                             |
| ---------- | :----: | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026-04-09 | 1.0.0  | Documento criado — PARTE 1 (mapeamento Inspinia) + PARTE 2 (catálogo) + PARTE 3 (estratégia portal)                                                                                                                                                                                                   |
| 2026-04-11 | 2.0.0  | **Reestruturado como índice oficial** — catálogo detalhado migra para `INSPINIA-CATALOGO-COMPONENTES.md`, mapa tela→componente migra para `INSPINIA-MAPA-TELAS-COMPONENTES.md`, parte 1 legada substituída por referência a `template/INSPINIA/README.md` + `TRIAGEM.md`; PARTE 3 (portal) preservada |
