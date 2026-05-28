# Mapa Tela → Componentes — Portal ArtFinal (Admin + Portal)

> **Índice operacional** que mapeia cada tela do PRD §14 para os componentes Blade (e views) que a compõem. Deve ser a **primeira parada** ao iniciar qualquer tela.
>
> **Como usar:** abra a seção da tela, veja os componentes listados, confirme que estão 🟢 (prontos) ou 🟡 (a validar); só então comece a escrever a view/Livewire.

**Versão:** 1.0.0
**Data:** 2026-04-11
**Documento pai:** [`04-TEMPLATE-MAP-AND-COMPONENTS.md`](04-TEMPLATE-MAP-AND-COMPONENTS.md)
**Catálogo oficial:** [`INSPINIA-CATALOGO-COMPONENTES.md`](INSPINIA-CATALOGO-COMPONENTES.md)
**Triagem:** [`template/INSPINIA/TRIAGEM.md`](template/INSPINIA/TRIAGEM.md)

---

## 1. Como ler este documento

Para cada tela do admin (`§14.x` do PRD) há uma seção com:

- **PRD:** link/âncora para a seção no PRD
- **Módulo:** módulo do backend (M01, M02, …) conforme `ARCHITECTURE-GUIDE.md`
- **Componentes principais:** base visual da tela — sem eles a tela não existe
- **Componentes auxiliares:** usados pontualmente (modal, drawer, toast)
- **Plugins JS envolvidos:** bibliotecas carregadas (Flatpickr, Inputmask, Choices.js, ApexCharts, etc.)
- **Status da tela:** 🔴 não iniciada / 🟡 parcial / 🟢 pronta
- **Observações:** marcações explícitas quando algo está `🟡 a validar`, está no `🅿️ parking lot`, ou depende de decisão pendente

### 1.1 Convenção de marcações inline

| Marcação          | Significado                                                         |
| ----------------- | ------------------------------------------------------------------- |
| `🟢`              | Componente/tela pronta                                              |
| `🟡 a validar`    | Pendente de decisão antes de codar                                  |
| `🔴 não iniciado` | Ainda não componentizado                                            |
| `🅿️ parking lot`  | Item deferido — se a tela precisar dele, promover primeiro          |
| `🔌 API-decision` | Depende da decisão de API-ready (interface DTO ou contrato Service) |

### 1.2 Consolidação oficial do Batch 3

- `x-shared.toast` é nome guarda-chuva da família de toast. A implementação real prevista para o lote é `x-shared.toast` + `x-shared.toast-container`, com disparo por browser events/helper JS.
- `x-shared.tabs` também é nome guarda-chuva no mapa. A API Blade final será a composição `x-shared.tab-nav` + `x-shared.tab-trigger` + `x-shared.tab-panel`.
- `x-shared.confirm-dialog` permanece no escopo como helper JS/bridge SweetAlert2. Não existe tag Blade `x-admin.confirm-modal` na fonte oficial.
- Não existe `x-admin.timeline-item` na fonte oficial. O único item reutilizável de timeline já catalogado é `x-admin.timeline-table`.
- `x-admin.programacao-timeline` não entra como componente oficial: a subtela 14.7 continua resolvida por `x-admin.timeline-table` + `x-shared.modal`.
- `x-shared.offcanvas` não existe separado do `x-admin.drawer` no escopo oficial atual, e `x-shared.popover` permanece fora do catálogo até ganhar doc própria.

---

## 2. Sumário por Tela

|                           #                            | Tela PRD                                                        | Módulo  | Status | Componentes-chave                                                                                |
| :----------------------------------------------------: | --------------------------------------------------------------- | :-----: | :----: | ------------------------------------------------------------------------------------------------ |
|             [14.1](#141-tela-login-admin)              | Login Admin                                                     |   M01   |   🔴   | `input`, `password-input`, `loading-button` + view `sign-in-split`                               |
|       [14.2](#142-tela-dashboard-administrativo)       | Dashboard Admin                                                 |   M20   |   🔴   | `kpi-card` (×4), `chart-bar`, `chart-line`, `progress-bar`, `alert`                              |
|        [14.3](#143-tela-gestão-de-instituições)        | Instituições                                                    |   M04   |   🔴   | `data-table`, `drawer`, `cnpj-input`, `cep-input`, `file-upload`                                 |
|         [14.4](#144-tela-gestão-de-contratos)          | Contratos                                                       |   M03   |   🔴   | `data-table`, `tabs` ×5, `select-search`, `date-picker`, `money-input`, `copy-button`            |
|   [14.5](#145-tela-gestão-de-categorias-de-produtos)   | Categorias                                                      |   M05   |   🔴   | `data-table`, `drawer`, `input`                                                                  |
|     [14.6](#146-tela-gestão-de-pacotes-e-produtos)     | Pacotes/Produtos                                                |   M05   |   🔴   | `data-table`, `tabs` ×5, `file-upload`, `select`, `money-input`, `textarea`                      |
| [14.7](#147-subtela-programações-de-valorparcelamento) | Programações (subtela)                                          |   M05   |   🔴   | `timeline-table`, `date-picker`, `money-input`                                                   |
|      [14.8](#148-subtela-condições-de-pagamento)       | Condições de Pagamento                                          |   M05   |   🔴   | `data-table` (inline), `money-input`, `select`                                                   |
|             [14.9](#149-subtela-descontos)             | Descontos                                                       |   M05   |   🔴   | `data-table`, `date-range-picker`, `money-input`                                                 |
|        [14.10](#1410-subtela-termos-do-produto)        | Termos do Produto                                               |   M05   |   🔴   | `sortable-list`, `select-search`                                                                 |
|          [14.11](#1411-tela-gestão-de-termos)          | Gestão de Termos                                                |   M06   |   🔴   | **editor rich text** 🟡, `data-table`, `tabs`                                                    |
|        [14.12](#1412-tela-gestão-de-formandos)         | Formandos                                                       |   M10   |   🔴   | `data-table`, `tabs` ×7, `list-group`, `status-badge`, `cpf-input`, `phone-input`, `file-upload` |
|    [14.13](#1413-tela-gestão-financeira--parcelas)     | Parcelas                                                        |   M14   |   🔴   | `data-table` (selectable), `status-badge`, `date-range-picker`, `modal`                          |
|     [14.14](#1414-tela-simulador-de-parcelamento)      | Simulador                                                       |   M14   |   🔴   | `money-input`, `select`, number input 🟡                                                         |
|       [14.15](#1415-tela-configurações-globais)        | Config. Globais                                                 |   M17   |   🔴   | `tabs`, `input`, `money-input`, `tags-input`, `file-upload`                                      |
|   [14.16](#1416-tela-gestão-de-índices-de-reajuste)    | Índices de Reajuste                                             |   M16   |   🔴   | `data-table`, `drawer`, `date-picker`, `money-input`                                             |
|             [14.17](#1417-tela-relatórios)             | Relatórios                                                      | M17-rpt |   🔴   | `data-table` (export), `chart-column`, `chart-pie`, `date-range-picker`                          |
|      [14.18](#1418-tela-gestão-de-usuários-admin)      | Usuários Admin                                                  |   M19   |   🔴   | `data-table`, `input`, `password-input` (with-meter), `status-badge`, `modal`                    |
| [14.19](#1419-tela-gestão-de-perfis-e-permissões-acl)  | ACL                                                             |   M19   |   🔴   | `tabs`, `checkbox` (matriz), `data-table`                                                        |
|    [14.20](#1420-tela-cadastro-manual-de-formando)     | Cadastro Manual                                                 |   M10   |   🔴   | `accordion` (8 sections), `cpf-input`, `phone-input`, `cep-input`, `date-picker`, `money-input`  |
|       [14.21](#1421-navegação-do-admin-sidebar)        | Sidebar Navegação                                               |   M00   |   🟢   | `x-admin.sidebar` (componente principal)                                                         |
|                           —                            | [Portal — Wizard Adesão](#p1-portal--wizard-de-adesão-7-etapas) |   M11   |   🔴   | `x-portal.wizard`, `input`, `select`, `money-input`, `cpf-input`, `password-input`               |
|                           —                            | [Portal — Área do Formando](#p2-portal--área-do-formando)       |   M12   |   🔴   | `formando-selector`, `parcela-card`, `package-card`, `section-card`                              |
|                           —                            | [Admin Account Settings](#u1-admin--account-settings)           |   M01   |   🔴   | `list-group`, `input`, `password-input`, `toggle` + view                                         |
|                           —                            | [Páginas de Erro](#u2-páginas-de-erro)                          |  Infra  |   🔴   | views `404/403/500/503` — sem componentes                                                        |

---

## 3. Admin — 21 telas do §14

### 14.1 — Tela: Login Admin

- **PRD:** §14.1
- **Módulo:** M01 (Autenticação Admin)
- **View:** `resources/views/admin/auth/login.blade.php` — referência Inspinia: [sign-in-split.md](template/INSPINIA/Pages/Auth/sign-in-split.md)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-shared.input` | Campo e-mail | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.password-input` | Campo senha (com toggle visibility) | [password-input.md](template/INSPINIA/Forms/password-input.md) |
| `x-shared.checkbox` | "Lembrar-me" | [checkbox.md](template/INSPINIA/Forms/checkbox.md) |
| `x-shared.loading-button` | Botão "Entrar" com estado loading Livewire | [loading-button.md](template/INSPINIA/Components/Feedback/loading-button.md) |
| `x-shared.alert` | Flash de erro de credenciais | [alert.md](template/INSPINIA/Components/UI/alert.md) |

**Componentes auxiliares:** `x-admin.partials.theme-bootstrap` (persistência dark/light antes do guard)
**Plugins JS:** nenhum (Livewire nativo)
**Status:** 🟢 concluída
**Observações:** fluxo "Esqueci minha senha" está no 🅿️ parking lot (`auth/reset-pass`, `new-pass`) — promover quando M01 ganhar reset de senha.

---

### 14.2 — Tela: Dashboard Administrativo

- **PRD:** §14.2
- **Módulo:** M20 (Dashboard Admin)
- **View referência Inspinia:** [analytics.md](template/INSPINIA/Dashboards/analytics.md)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.kpi-card` ×4 | Contratos ativos, Formandos, Receita mês, Inadimplência | [kpi-card.md](template/INSPINIA/Components/Data/kpi-card.md) |
| `x-admin.chart-bar` | Gráfico "Adesões por Mês" | [bar.md](template/INSPINIA/Charts/ApexCharts/bar.md) |
| `x-admin.chart-line` | Gráfico dual "Receita × Inadimplência" | [line.md](template/INSPINIA/Charts/ApexCharts/line.md) |
| `x-shared.progress-bar` | Meta de formandos (% atingido) | [progress.md](template/INSPINIA/Components/UI/progress.md) |
| `x-shared.alert` | Seção "Alertas do sistema" | [alert.md](template/INSPINIA/Components/UI/alert.md) |
| `x-admin.chart-card` | Wrapper para os 2 gráficos | [chart-card.md](template/INSPINIA/Charts/chart-card.md) |

**Plugins JS:** ApexCharts 5.3.5
**Status:** 🟢 componentização concluída
**Observações:**

- os wrappers `chart-bar` e `chart-line` já estão disponíveis no catálogo de previews e compartilham a bridge `resources/js/admin/charts.js`
- 🅿️ Sparklines nos KPI cards (tendência mini-gráfico) está no parking lot — promover em fase futura se quisermos evolução visual
- 🅿️ Radialbar para meta de formandos é alternativa visual à progress-bar tradicional
- 🔌 Os dois gráficos consomem `Livewire.on('chart-update', …)` via `charts-bridge.js` — pattern registrado em [chart-card.md](template/INSPINIA/Charts/chart-card.md)
- `metric`, `widget`, `formando-card` e `parcela-row` não entram como componentes oficiais neste ciclo; dashboard/data display seguem com `kpi-card`, `chart-card`, `progress-bar` e composição das views específicas

---

### 14.3 — Tela: Gestão de Instituições

- **PRD:** §14.3
- **Módulo:** M04 (Instituições)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` | Listagem com search + export | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-admin.drawer` | Form lateral de cadastro/edição rápida | [drawer.md](template/INSPINIA/Components/UI/drawer.md) |
| `x-shared.input` | Nome, razão social, inscrição | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.cnpj-input` | CNPJ com máscara | [cnpj-input.md](template/INSPINIA/Forms/cnpj-input.md) |
| `x-shared.cep-input` | CEP com fetch ViaCEP | [cep-input.md](template/INSPINIA/Forms/cep-input.md) |
| `x-shared.select` | UF (dropdown estado) | [select.md](template/INSPINIA/Forms/select.md) |
| `x-shared.file-upload` | Upload logo da instituição | [file-upload.md](template/INSPINIA/Forms/file-upload.md) |
| `x-shared.status-badge` | Ativo/Inativo | [status-badge.md](template/INSPINIA/Components/Data/status-badge.md) |

**Componentes auxiliares:** `x-shared.confirm-dialog` (confirmação de inativação), `x-shared.toast` (feedback)
**Plugins JS:** Inputmask (CNPJ/CEP), Dropzone (opcional — `file-upload` aceita), DataTables (via `data-table`)
**Status:** 🔴 não iniciada
**Observações:**

- DataTable com `:exportable`, `:searchable`, `:column-search`
- `cep-input` disparar `$dispatch('cep-filled', …)` para preencher logradouro/bairro/cidade/uf
- Ações por linha reutilizam `x-shared.dropdown`; não existe `x-admin.action-dropdown` separado no escopo oficial

---

### 14.4 — Tela: Gestão de Contratos

- **PRD:** §14.4
- **Módulo:** M03 (Contratos)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` | Listagem de contratos | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-shared.tabs` ×5 | Dados gerais / Pacotes / Datas / Termos / Histórico | [tabs.md](template/INSPINIA/Components/UI/tabs.md) |
| `x-shared.select-search` | Instituição, Curso (Choices.js) | [select-search.md](template/INSPINIA/Forms/select-search.md) |
| `x-shared.date-picker` | Data início, data evento, vencimentos | [date-picker.md](template/INSPINIA/Forms/date-picker.md) |
| `x-shared.money-input` | Valores do contrato | [money-input.md](template/INSPINIA/Forms/money-input.md) |
| `x-shared.toggle` | "Exige responsável", "Exige atestado" | [toggle.md](template/INSPINIA/Forms/toggle.md) |
| `x-shared.input` | Nome da turma, meta formandos | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.dropdown` | Menu de ações por linha | [dropdown.md](template/INSPINIA/Components/UI/dropdown.md) |
| `x-shared.copy-button` | Copiar código turma, link público de adesão | [clipboard.md](template/INSPINIA/Plugins/clipboard.md) |
| `x-shared.status-badge` | Status do contrato | [status-badge.md](template/INSPINIA/Components/Data/status-badge.md) |

**Componentes auxiliares:** `x-shared.confirm-dialog` (confirmar cancelamento), `x-shared.tooltip` (explicações)
**Plugins JS:** Flatpickr, Choices.js, Inputmask (money), DataTables
**Status:** 🔴 não iniciada
**Observações:**

- Aba "Termos" usa 🅿️ `text-diff` para comparar versões — promover se necessário
- Snapshot imutável (PRD §17) fotografa valores no momento da adesão — sem impacto no UI mas deve ser respeitado pela Service

---

### 14.5 — Tela: Gestão de Categorias de Produtos

- **PRD:** §14.5
- **Módulo:** M05 (Produtos)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` | Listagem de categorias | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-admin.drawer` | Form de cadastro inline | [drawer.md](template/INSPINIA/Components/UI/drawer.md) |
| `x-shared.input` | Nome, slug | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.textarea` | Descrição | [textarea.md](template/INSPINIA/Forms/textarea.md) |
| `x-shared.toggle` | Ativo | [toggle.md](template/INSPINIA/Forms/toggle.md) |
| `x-shared.status-badge` | Ativo/Inativo | [status-badge.md](template/INSPINIA/Components/Data/status-badge.md) |

**Componentes auxiliares:** `x-shared.confirm-dialog` (confirmação de exclusão/inativação), `x-shared.toast`
**Plugins JS:** DataTables
**Status:** 🔴 não iniciada

---

### 14.6 — Tela: Gestão de Pacotes e Produtos

- **PRD:** §14.6
- **Módulo:** M05

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` | Listagem de pacotes/produtos | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-shared.tabs` ×5 | Dados / Programações / Condições Pgto / Descontos / Termos | [tabs.md](template/INSPINIA/Components/UI/tabs.md) |
| `x-shared.file-upload` | Imagem do produto | [file-upload.md](template/INSPINIA/Forms/file-upload.md) |
| `x-shared.select` | Categoria | [select.md](template/INSPINIA/Forms/select.md) |
| `x-shared.money-input` | Valor base | [money-input.md](template/INSPINIA/Forms/money-input.md) |
| `x-shared.textarea` | Descrição | [textarea.md](template/INSPINIA/Forms/textarea.md) |
| `x-shared.toggle` | Ativo, "permite parcelar" | [toggle.md](template/INSPINIA/Forms/toggle.md) |

**Componentes auxiliares:** as tabs 2, 3, 4 e 5 usam os componentes de 14.7, 14.8, 14.9 e 14.10 (subtelas)
**Plugins JS:** Inputmask (money), Dropzone (opcional), DataTables
**Status:** 🔴 não iniciada
**Observações:**

- Cada tab corresponde a uma subtela descrita em 14.7–14.10
- Reeditar imagem: 🅿️ FilePond se quisermos crop; por ora Dropzone via `file-upload` atende

---

### 14.7 — Subtela: Programações de Valor/Parcelamento

- **PRD:** §14.7
- **Módulo:** M05

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.timeline-table` | Linha do tempo de programações | [custom-table.md](template/INSPINIA/Tables/custom-table.md) |
| `x-shared.date-picker` | Início/fim de vigência | [date-picker.md](template/INSPINIA/Forms/date-picker.md) |
| `x-shared.money-input` | Valor parcela mínima, valor total | [money-input.md](template/INSPINIA/Forms/money-input.md) |
| `x-shared.input` | Número máximo de parcelas | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.select` | Modalidade (boleto/PIX/cartão) | [select.md](template/INSPINIA/Forms/select.md) |

**Componentes auxiliares:** `x-shared.modal` (editar programação), `x-shared.confirm-dialog` (remover programação ativa)
**Plugins JS:** Flatpickr, Inputmask
**Status:** 🔴 não iniciada
**Observações:** é a única tela que usa `timeline-table` — não generalizar este componente para outras telas antes de avaliar reuso; não existe `x-admin.timeline-item` separado na fonte oficial; a timeline de programação segue sem componente autônomo adicional; `x-admin.timeline-table` já está implementado e disponível no catálogo de previews

---

### 14.8 — Subtela: Condições de Pagamento

- **PRD:** §14.8
- **Módulo:** M05

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-shared.static-table` ou `x-admin.data-table` (inline) | Matriz de parcelas × modalidades | [static-table.md](template/INSPINIA/Tables/static-table.md) |
| `x-shared.money-input` | Valor de parcela por linha | [money-input.md](template/INSPINIA/Forms/money-input.md) |
| `x-shared.select` | Modalidade (boleto/PIX/cartão) | [select.md](template/INSPINIA/Forms/select.md) |
| `x-shared.input` | Número de parcelas | [input.md](template/INSPINIA/Forms/input.md) |

**Plugins JS:** Inputmask
**Status:** 🔴 não iniciada
**Observações:** 🔌 API-decision — depende de saber se a matriz é editada linha-a-linha (static-table) ou se vira DataTable com filtros (decisão de UX); `x-shared.static-table` já está implementado e pode ser usado imediatamente nos cenários simples

---

### 14.9 — Subtela: Descontos

- **PRD:** §14.9
- **Módulo:** M05

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` | Lista de descontos ativos | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-shared.date-range-picker` | Vigência do desconto | [date-range-picker.md](template/INSPINIA/Forms/date-range-picker.md) |
| `x-shared.money-input` | Valor/percentual do desconto | [money-input.md](template/INSPINIA/Forms/money-input.md) |
| `x-shared.select` | Tipo (fixo/percentual) | [select.md](template/INSPINIA/Forms/select.md) |
| `x-shared.toggle` | Cumulativo? | [toggle.md](template/INSPINIA/Forms/toggle.md) |

**Plugins JS:** Flatpickr, Inputmask
**Status:** 🔴 não iniciada

---

### 14.10 — Subtela: Termos do Produto

- **PRD:** §14.10
- **Módulo:** M05

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.sortable-list` | Drag-and-drop de termos | [sortable.md](template/INSPINIA/Plugins/sortable.md) |
| `x-shared.select-search` | Seleção de termos a vincular | [select-search.md](template/INSPINIA/Forms/select-search.md) |
| `x-shared.badge` | Versão do termo (v1, v2) | [badge.md](template/INSPINIA/Components/UI/badge.md) |

**Componentes auxiliares:** `x-shared.button` (desvincular), `x-shared.toast`
**Plugins JS:** SortableJS 1.15.6, Choices.js
**Status:** 🔴 não iniciada
**Observações:** Handle explícito `.drag-handle` — evita arrastar ao clicar no botão delete (ver nota em sortable.md)

---

### 14.11 — Tela: Gestão de Termos

- **PRD:** §14.11
- **Módulo:** M06 (Termos)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` | Lista de termos (com versões) | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-shared.tabs` | Metadados / Editor / Histórico de versões | [tabs.md](template/INSPINIA/Components/UI/tabs.md) |
| **Editor rich text** 🟡 | Edição do corpo do termo | (ver observações) |
| `x-shared.input` | Nome, slug, escopo | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.select` | Tipo do termo (geral/pagamento/privacidade) | [select.md](template/INSPINIA/Forms/select.md) |
| `x-shared.badge` | Versão ativa/rascunho | [badge.md](template/INSPINIA/Components/UI/badge.md) |

**Componentes auxiliares:** `x-shared.modal` (pré-visualizar), `x-shared.confirm-dialog` (arquivar versão)
**Plugins JS:** DataTables + **decisão pendente** (Quill **ou** TinyMCE self-host)
**Status:** 🔴 não iniciada
**Observações:**

- 🟡 **a validar — editor rich text:** Inspinia tem Quill; TinyMCE é pago/ausente. Registrar decisão em `docs/02-CONVENTIONS.md` seção "Decisões de UI" antes de codar
- 🅿️ `plugins/text-diff.blade.php` do parking lot é candidato natural para a aba "Histórico de versões" (diff v1 ↔ v2)
- 🅿️ `plugins/pdf-viewer.blade.php` pode gerar preview inline do termo consolidado em PDF

---

### 14.12 — Tela: Gestão de Formandos

- **PRD:** §14.12
- **Módulo:** M10 (Formandos)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` | Listagem com filtros avançados | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-shared.tabs` ×7 | Dados / Adesões / Parcelas / Pagamentos / Documentos / Comunicação / Histórico | [tabs.md](template/INSPINIA/Components/UI/tabs.md) |
| `x-shared.list-group` | Sidebar da ficha do formando (navegação entre seções) | [list-group.md](template/INSPINIA/Components/UI/list-group.md) |
| `x-shared.status-badge` | Status adesão, adimplência, ativo/inativo | [status-badge.md](template/INSPINIA/Components/Data/status-badge.md) |
| `x-shared.cpf-input` | CPF do formando | [cpf-input.md](template/INSPINIA/Forms/cpf-input.md) |
| `x-shared.phone-input` | Telefone | [phone-input.md](template/INSPINIA/Forms/phone-input.md) |
| `x-shared.cep-input` | CEP | [cep-input.md](template/INSPINIA/Forms/cep-input.md) |
| `x-shared.date-picker` | Data nascimento, admissão | [date-picker.md](template/INSPINIA/Forms/date-picker.md) |
| `x-shared.date-range-picker` | Filtros de data (aba adesões, pagamentos) | [date-range-picker.md](template/INSPINIA/Forms/date-range-picker.md) |
| `x-shared.file-upload` | Foto do formando, documentos | [file-upload.md](template/INSPINIA/Forms/file-upload.md) |
| `x-shared.copy-button` | Copiar CPF formatado, e-mail | [clipboard.md](template/INSPINIA/Plugins/clipboard.md) |

**Componentes auxiliares:** `x-shared.modal`, `x-shared.confirm-dialog`, `x-shared.toast`, `x-shared.dropdown` (ações por linha), `x-admin.drawer` (filtros avançados)
**Plugins JS:** Flatpickr, Inputmask, Choices.js, DataTables, Dropzone (opcional)
**Status:** 🔴 não iniciada
**Observações:**

- **Tela com mais componentes do sistema** — é o CRUD mais denso do admin
- 🅿️ A aba 7 "Histórico" usa o estilo visual de `pages/timeline.blade.php` como referência (não vira componente; inspira a view)
- 🅿️ File Manager (`apps/file-manager.blade.php`) do parking lot é candidato para a aba "Documentos" se ficar grande
- Filtros avançados continuam composição com `x-admin.drawer` + forms base; não existe `x-admin.filter-panel` autônomo nas fontes oficiais

---

### 14.13 — Tela: Gestão Financeira / Parcelas

- **PRD:** §14.13
- **Módulo:** M14 (Parcelas/Financeiro)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` (com `:selectable`) | Listagem com baixa em lote | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-shared.status-badge` | Pendente / Pago / Vencido / Cancelado (Enum `StatusParcela`) | [status-badge.md](template/INSPINIA/Components/Data/status-badge.md) |
| `x-shared.date-range-picker` | Filtros de vencimento | [date-range-picker.md](template/INSPINIA/Forms/date-range-picker.md) |
| `x-shared.select` | Modalidade, status | [select.md](template/INSPINIA/Forms/select.md) |
| `x-shared.modal` | Baixa manual, reemitir boleto | [modal.md](template/INSPINIA/Components/UI/modal.md) |
| `x-shared.money-input` | Valor de baixa manual | [money-input.md](template/INSPINIA/Forms/money-input.md) |
| `x-shared.date-picker` | Data da baixa | [date-picker.md](template/INSPINIA/Forms/date-picker.md) |
| `x-shared.confirm-dialog` | Confirmar cancelamento/estorno | [confirm-dialog.md](template/INSPINIA/Components/Feedback/confirm-dialog.md) |
| `x-admin.drawer` | Filtros avançados | [drawer.md](template/INSPINIA/Components/UI/drawer.md) |

**Componentes auxiliares:** `x-shared.toast`, `x-shared.dropdown` (ações por linha), `x-shared.copy-button` (ID da parcela, código boleto)
**Plugins JS:** DataTables (selectable, export, column-search, range-search), Flatpickr, Inputmask
**Status:** 🔴 não iniciada
**Observações:**

- Baixa em lote usa `:selectable` do DataTable — confirmar com Enum `StatusParcela`
- 🅿️ `datatables/child-rows.blade.php` do parking lot serve para expandir a parcela e mostrar histórico de tentativas de pagamento
- Bulk actions e export ficam dentro do contrato do `x-admin.data-table`; não existe `x-admin.bulk-actions` nem `x-admin.export-buttons` como componentes separados

---

### 14.14 — Tela: Simulador de Parcelamento

- **PRD:** §14.14
- **Módulo:** M14

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-shared.money-input` | Valor total da simulação | [money-input.md](template/INSPINIA/Forms/money-input.md) |
| `x-shared.select` | Modalidade de pagamento | [select.md](template/INSPINIA/Forms/select.md) |
| `x-shared.input` (type=number) 🟡 | Número de parcelas | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.static-table` | Tabela de resultado (parcelas × valores × juros) | [static-table.md](template/INSPINIA/Tables/static-table.md) |
| `x-shared.card` | Moldura dos resultados | [card.md](template/INSPINIA/Components/UI/card.md) |

**Plugins JS:** Inputmask (money)
**Status:** 🔴 não iniciada
**Observações:**

- 🟡 **a validar — slider vs number input:** o Inspinia tem noUiSlider no parking lot para escolha visual do número de parcelas. Número input simples é mais rápido e atende. Decisão registrada em `docs/02-CONVENTIONS.md` antes de codar
- 🔌 API-decision: a Service `ParcelamentoCalculatorService` já está nomeada no PRD — ela retorna um `ParcelamentoCalculoDTO` com `toArray()` (ver CLAUDE.md §7.5)

---

### 14.15 — Tela: Configurações Globais

- **PRD:** §14.15
- **Módulo:** M17 (Configurações)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-shared.tabs` | Categorias de config (Empresa / Financeiro / Notificações / Integrações) | [tabs.md](template/INSPINIA/Components/UI/tabs.md) |
| `x-shared.input` | Nome da empresa, e-mail, site | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.textarea` | Rodapé de documentos, mensagens | [textarea.md](template/INSPINIA/Forms/textarea.md) |
| `x-shared.money-input` | Valores default (taxa de juros, multa) | [money-input.md](template/INSPINIA/Forms/money-input.md) |
| `x-shared.cnpj-input` | CNPJ da empresa | [cnpj-input.md](template/INSPINIA/Forms/cnpj-input.md) |
| `x-shared.cep-input` | CEP sede | [cep-input.md](template/INSPINIA/Forms/cep-input.md) |
| `x-shared.file-upload` | Logo da empresa, favicon | [file-upload.md](template/INSPINIA/Forms/file-upload.md) |
| `x-shared.select-search` | Gateway default, banco default | [select-search.md](template/INSPINIA/Forms/select-search.md) |
| `x-shared.toggle` | Flags de feature (ex: exige 2FA) | [toggle.md](template/INSPINIA/Forms/toggle.md) |
| `x-shared.tags-input` | Dias de vencimento, dias de lembrete | [tags-input.md](template/INSPINIA/Forms/tags-input.md) |

**Componentes auxiliares:** `x-shared.alert` (warnings de config), `x-shared.toast`
**Plugins JS:** Flatpickr, Inputmask, Choices.js, Dropzone (opcional)
**Status:** 🔴 não iniciada
**Observações:**

- `x-shared.tags-input` foi consolidado como wrapper semântico sobre Choices.js multiple
- 🔌 API-decision: structure das configs virão de um DTO `ConfiguracaoGlobalDTO` — `Service` em vez de Eloquent direto

---

### 14.16 — Tela: Gestão de Índices de Reajuste

- **PRD:** §14.16
- **Módulo:** M16 (Reajustes)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` | Listagem histórica | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-admin.drawer` | Form de lançamento de novo índice | [drawer.md](template/INSPINIA/Components/UI/drawer.md) |
| `x-shared.date-picker` | Mês de referência | [date-picker.md](template/INSPINIA/Forms/date-picker.md) |
| `x-shared.input` | Percentual do índice | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.select` | Tipo do índice (IGPM/IPCA/INCC/custom) | [select.md](template/INSPINIA/Forms/select.md) |

**Plugins JS:** Flatpickr, DataTables
**Status:** 🔴 não iniciada

---

### 14.17 — Tela: Relatórios

- **PRD:** §14.17
- **Módulo:** M17-rpt (Relatórios)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` (com `:exportable`) | Grid tabular do relatório | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-admin.chart-column` | Receita por contrato | [column.md](template/INSPINIA/Charts/ApexCharts/column.md) |
| `x-admin.chart-pie` | Distribuição por modalidade de pagamento | [pie.md](template/INSPINIA/Charts/ApexCharts/pie.md) |
| `x-admin.chart-card` | Moldura dos gráficos | [chart-card.md](template/INSPINIA/Charts/chart-card.md) |
| `x-shared.date-range-picker` | Filtro de período | [date-range-picker.md](template/INSPINIA/Forms/date-range-picker.md) |
| `x-shared.select` | Tipo do relatório, agrupamento | [select.md](template/INSPINIA/Forms/select.md) |
| `x-admin.drawer` | Configuração do filtro avançado | [drawer.md](template/INSPINIA/Components/UI/drawer.md) |
| `x-admin.kpi-card` | Totalizadores do período | [kpi-card.md](template/INSPINIA/Components/Data/kpi-card.md) |

**Componentes auxiliares:** `x-shared.loading-button` (gerar relatório), `x-shared.toast`
**Plugins JS:** ApexCharts, Flatpickr, DataTables (com export)
**Status:** 🟢 componentização base concluída
**Observações:**

- 🅿️ `charts/apex/area.blade.php`, `heatmap.blade.php`, `radialbar.blade.php`, `treemap.blade.php`, `sparklines.blade.php` no parking lot — promover conforme relatórios evoluírem
- 🅿️ Vector map (`maps/vector.blade.php`) do parking lot é candidato para relatório geográfico (formandos por estado)
- `chart-column` e `chart-pie` já estão disponíveis como wrappers reutilizáveis no catálogo de previews

---

### 14.18 — Tela: Gestão de Usuários Admin

- **PRD:** §14.18
- **Módulo:** M19 (Usuários Admin / ACL)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.data-table` | Listagem de usuários admin | [data-table.md](template/INSPINIA/Tables/data-table.md) |
| `x-admin.drawer` | Form de cadastro/edição | [drawer.md](template/INSPINIA/Components/UI/drawer.md) |
| `x-shared.input` | Nome, e-mail | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.password-input` **com prop `:with-meter`** | Senha com medidor de força | [password-input.md](template/INSPINIA/Forms/password-input.md) |
| `x-shared.password-strength-meter` | Medidor embutido | [pass-meter.md](template/INSPINIA/Plugins/pass-meter.md) |
| `x-shared.select-search` | Perfil ACL (Spatie role) | [select-search.md](template/INSPINIA/Forms/select-search.md) |
| `x-shared.status-badge` | Ativo/Inativo/Bloqueado | [status-badge.md](template/INSPINIA/Components/Data/status-badge.md) |
| `x-shared.modal` | Reset de senha, bloquear | [modal.md](template/INSPINIA/Components/UI/modal.md) |
| `x-shared.confirm-dialog` | Confirmar inativação | [confirm-dialog.md](template/INSPINIA/Components/Feedback/confirm-dialog.md) |

**Plugins JS:** DataTables, Choices.js
**Status:** 🟢 componentização base concluída
**Observações:**

- 🅿️ 2FA (`auth-*/two-factor.blade.php`) do parking lot quando M19 evoluir para security
- `password-input` com prop `:with-meter` já embute `password-strength-meter`

---

### 14.19 — Tela: Gestão de Perfis e Permissões ACL

- **PRD:** §14.19
- **Módulo:** M19

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-shared.tabs` | Por perfil (Admin / Gerente / Operador / Auditor) | [tabs.md](template/INSPINIA/Components/UI/tabs.md) |
| `x-shared.checkbox` | Matriz de permissões | [checkbox.md](template/INSPINIA/Forms/checkbox.md) |
| `x-shared.static-table` | Tabela da matriz (linhas = permissions, colunas = módulos) | [static-table.md](template/INSPINIA/Tables/static-table.md) |
| `x-shared.input` | Nome do perfil, descrição | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.loading-button` | Salvar matriz | [loading-button.md](template/INSPINIA/Components/Feedback/loading-button.md) |
| `x-shared.toast` | Feedback | [toast.md](template/INSPINIA/Components/Feedback/toast.md) |

**Plugins JS:** nenhum (Livewire puro)
**Status:** 🔴 não iniciada
**Observações:**

- Usa `spatie/laravel-permission` — nenhum componente ACL vem do Inspinia (`apps/users/permissions.blade.php` está nos descartados)
- 🅿️ `plugins/tree-view.blade.php` do parking lot é alternativa visual à matriz (árvore hierárquica) — promover se a matriz ficar inconveniente

---

### 14.20 — Tela: Cadastro Manual de Formando (Adesão pelo Admin)

- **PRD:** §14.20
- **Módulo:** M10

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-shared.accordion` ×8 | Seções do formulário longo (Dados pessoais / Endereço / Responsável / Instituição / Contrato / Pacote / Pagamento / Revisão) | [accordion.md](template/INSPINIA/Components/UI/accordion.md) |
| `x-shared.cpf-input` | CPF formando + responsável | [cpf-input.md](template/INSPINIA/Forms/cpf-input.md) |
| `x-shared.phone-input` | Telefones | [phone-input.md](template/INSPINIA/Forms/phone-input.md) |
| `x-shared.cep-input` | CEP | [cep-input.md](template/INSPINIA/Forms/cep-input.md) |
| `x-shared.date-picker` | Data nasc., data admissão | [date-picker.md](template/INSPINIA/Forms/date-picker.md) |
| `x-shared.money-input` | Valores acordados | [money-input.md](template/INSPINIA/Forms/money-input.md) |
| `x-shared.select-search` | Contrato, produto, modalidade | [select-search.md](template/INSPINIA/Forms/select-search.md) |
| `x-shared.file-upload` | Documentos anexados | [file-upload.md](template/INSPINIA/Forms/file-upload.md) |
| `x-shared.input`, `x-shared.textarea`, `x-shared.toggle`, `x-shared.radio` | Campos diversos | — |
| `x-shared.loading-button` | Salvar adesão | [loading-button.md](template/INSPINIA/Components/Feedback/loading-button.md) |
| `x-shared.alert` | Erros de validação cruzada | [alert.md](template/INSPINIA/Components/UI/alert.md) |

**Componentes auxiliares:** `x-shared.confirm-dialog` (confirmar submissão), `x-shared.toast`
**Plugins JS:** Flatpickr, Inputmask, Choices.js, Dropzone (opcional)
**Status:** 🔴 não iniciada
**Observações:**

- **NÃO** usa `x-portal.wizard` — o admin prefere accordion (todas as seções visíveis, saltando entre elas livremente). Wizard é exclusivo do portal do formando
- 🔌 API-decision: reusa a `Action` `CreateAdesaoFromWizardAction` com source="admin_manual" (mesmo pipeline do wizard)

---

### 14.21 — Navegação do Admin (Sidebar)

- **PRD:** §14.21
- **Módulo:** M00 (Navegação base)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-admin.sidebar` | Menu lateral completo | [sidebar.md](template/INSPINIA/Components/Navigation/sidebar.md) |
| `x-admin.topbar` | Header com user, notificações, breadcrumb | [topbar.md](template/INSPINIA/Components/Navigation/topbar.md) |
| `x-admin.footer` | Rodapé simples | [footer.md](template/INSPINIA/Components/Navigation/footer.md) |
| `x-admin.page-header` | Título + breadcrumb + ações | [page-header.md](template/INSPINIA/Components/Navigation/page-header.md) |

**Plugins JS:** Iconify (Tabler/Lucide)
**Status:** 🔴 não iniciada
**Observações:**

- Menu usa `@can('...')` do Spatie para ocultar itens sem permissão
- Skin/config oficial: `skin=default`, `sidenav-color=dark`, `topbar-color=light`, `sidenav-size=default`, `width=fluid`, `position=fixed`
- `x-admin.topbar` compõe `x-shared.dropdown` (user) + `x-shared.badge` (notif count) — depende da Onda 2 completa
- `mega-menu` foi removido do escopo do ArtFinal; a navegação principal continua toda no `x-admin.sidebar`
- o sino/notificações permanece parte interna do `x-admin.topbar` e não vira componente autônomo nesta fase

---

## 4. Portal do Formando

> **Decisão pendente (Sprint 4):** template do portal (Preline UI, Tailwind puro ou híbrido). Enquanto a decisão não é tomada, os componentes abaixo estão baseados nos namespaces já definidos (`x-portal.*` + reuso de `x-shared.*`).

### P1. Portal — Wizard de Adesão (7 etapas)

- **PRD:** §11 (Wizard Portal), §20.3 Sprints 4–9
- **Módulo:** M11 (Adesão)

**Componentes principais:**
| Componente | Uso | Doc |
|------------|-----|-----|
| `x-portal.wizard` | Stepper server-driven com 7 etapas | [wizard.md](template/INSPINIA/Forms/wizard.md) |
| `x-portal.wizard-step` | Conteúdo de cada etapa | (subcomponente de wizard) |
| `x-shared.input` | Nome, e-mail, etc. | [input.md](template/INSPINIA/Forms/input.md) |
| `x-shared.cpf-input` | CPF do formando e responsável | [cpf-input.md](template/INSPINIA/Forms/cpf-input.md) |
| `x-shared.phone-input` | Telefones | [phone-input.md](template/INSPINIA/Forms/phone-input.md) |
| `x-shared.cep-input` | CEP | [cep-input.md](template/INSPINIA/Forms/cep-input.md) |
| `x-shared.date-picker` | Data nascimento | [date-picker.md](template/INSPINIA/Forms/date-picker.md) |
| `x-shared.select` / `x-shared.select-search` | Modalidade, curso | [select.md](template/INSPINIA/Forms/select.md) |
| `x-shared.money-input` | Valores | [money-input.md](template/INSPINIA/Forms/money-input.md) |
| `x-shared.password-input` (com meter) | Senha do portal_user | [password-input.md](template/INSPINIA/Forms/password-input.md) |
| `x-shared.checkbox` | Aceite de termos | [checkbox.md](template/INSPINIA/Forms/checkbox.md) |
| `x-shared.card` | Resumo da adesão | [card.md](template/INSPINIA/Components/UI/card.md) |
| `x-shared.button` / `x-shared.loading-button` | Avançar / Voltar / Concluir | [button.md](template/INSPINIA/Components/UI/button.md), [loading-button.md](template/INSPINIA/Components/Feedback/loading-button.md) |

**Plugins JS:** Flatpickr, Inputmask, Choices.js
**Status:** 🔴 não iniciada
**Observações:**

- 🟡 **a validar — template portal:** Preline UI vs Tailwind puro — decisão Sprint 4
- 🔌 API-decision: integra com `CreateAdesaoFromWizardAction` que retorna `AdesaoResultDTO`
- 🅿️ Funnel chart (`charts/apex/funnel.blade.php`) do parking lot é candidato natural para medir conversão do wizard

---

### P2. Portal — Área do Formando

- **PRD:** §11 (Portal Área), §20.3 Sprints 10–11
- **Módulo:** M12 (Área Formando)

**Componentes principais (a validar após decisão de template):**
| Componente | Uso | Observação |
|------------|-----|------------|
| `x-portal.formando-selector` | Seletor multi-formando (responsável com vários dependentes) | 🔴 a criar |
| `x-portal.parcela-card` | Card de parcela no extrato | 🔴 a criar |
| `x-portal.package-card` | Card de pacote/produto para compra de extras | 🔴 a criar |
| `x-portal.section-card` | Card de seção (dashboard do formando) | 🔴 a criar |
| `x-shared.status-badge` | Status de parcelas/adesões | [status-badge.md](template/INSPINIA/Components/Data/status-badge.md) |
| `x-shared.alert` | Avisos (vencimentos, ações pendentes) | [alert.md](template/INSPINIA/Components/UI/alert.md) |
| `x-shared.button`, `x-shared.loading-button` | Ações | — |
| `x-shared.modal` | Confirmações (ex: reemitir boleto) | [modal.md](template/INSPINIA/Components/UI/modal.md) |

**Status:** 🔴 não iniciada
**Observações:**

- 🟡 **a validar — template portal** (ver P1)
- Componentes `x-portal.*` ainda **não** estão documentados em `docs/template/INSPINIA/` porque o Inspinia é só admin — serão catalogados após a decisão de template do portal
- Após a decisão, criar um arquivo de catálogo específico para o portal (ex: `docs/PORTAL-CATALOGO-COMPONENTES.md`)

---

## 5. Pages utilitárias do admin

### U1. Admin — Account Settings

- **PRD:** §14.1 + apêndice
- **Módulo:** M01

**View:** `admin/account/settings.blade.php` — [account-settings.md](template/INSPINIA/Pages/Utility/account-settings.md)

Com 3 sub-rotas:

- `admin/account/perfil.blade.php`
- `admin/account/senha.blade.php`
- `admin/account/notificacoes.blade.php`

**Componentes usados:**
| Componente | Uso |
|------------|-----|
| `x-shared.list-group` | Nav entre sub-rotas (sidebar da página) |
| `x-shared.input` | Nome, e-mail, etc. |
| `x-shared.password-input` (with-meter) | Alterar senha |
| `x-shared.toggle` | Preferências de notificação (por evento) |
| `x-shared.file-upload` | Foto de perfil |
| `x-shared.alert` | Confirmação de alteração de e-mail (aguardando confirmação) |
| `x-shared.loading-button` | Salvar |

**Plugins JS:** Inputmask (telefone), Dropzone (opcional)
**Status:** 🔴 não iniciada
**Observações:** 🅿️ `pages/profile.blade.php` é uma alternativa visual (perfil público) — por ora só as 3 sub-rotas utilitárias

---

### U2. Páginas de Erro

- **PRD:** — (infra)
- **Views:** `errors/404.blade.php`, `errors/403.blade.php`, `errors/500.blade.php`, `errors/503.blade.php`

| View                   | Doc                                                            |
| ---------------------- | -------------------------------------------------------------- |
| `errors/404.blade.php` | [404.md](template/INSPINIA/Pages/Error/404.md)                 |
| `errors/403.blade.php` | [403.md](template/INSPINIA/Pages/Error/403.md)                 |
| `errors/500.blade.php` | [500.md](template/INSPINIA/Pages/Error/500.md)                 |
| `errors/503.blade.php` | [maintenance.md](template/INSPINIA/Pages/Error/maintenance.md) |

**Componentes usados:** `x-admin.partials.theme-bootstrap` (dark/light mesmo sem guard), `x-shared.alert` (opcional, debug info em dev)
**Plugins JS:** nenhum
**Status:** 🔴 não iniciada
**Observações:** Nenhuma dessas views é componente — são páginas completas, mas compartilham o bootstrap de tema para consistência visual

---

## 6. Cobertura × Checagem final

### 6.1 Telas 100% cobertas por componentes existentes no catálogo

- **Todas as 21 telas do admin** têm seus componentes principais **registrados** no catálogo.
- **Gaps** (itens 🟡 a validar) estão concentrados em:
    - Editor rich text (14.11 Termos) — Quill vs TinyMCE
    - Tags input (14.15 Config. Globais) — Choices multiple vs Tagify
    - Slider de parcelas (14.14 Simulador) — number input vs noUiSlider
    - Tooltip (transversal) — Alpine inline vs componente

### 6.2 Telas com dependências 🔌 API-decision

| Tela                        | Depende de                                      | Status da decisão             |
| --------------------------- | ----------------------------------------------- | ----------------------------- |
| 14.8 Condições de Pagamento | Estrutura da matriz (static-table vs datatable) | A decidir quando começar M05  |
| 14.14 Simulador             | `ParcelamentoCalculoDTO`                        | DTO já nomeado no PRD — OK    |
| 14.15 Config. Globais       | `ConfiguracaoGlobalDTO`                         | A criar no M17                |
| 14.20 Cadastro Manual       | `CreateAdesaoFromWizardAction`                  | Action já nomeada no PRD — OK |
| P1 Portal Wizard            | `AdesaoResultDTO`                               | DTO já nomeado no PRD — OK    |

### 6.3 Telas que dependem do Portal (template pendente)

- P1 Portal Wizard — 🟡 decisão Sprint 4
- P2 Portal Área — 🟡 decisão Sprint 4

Enquanto a decisão não existe, os componentes `x-shared.*` usados por essas telas **já estão prontos no catálogo** e podem ser reusados — só os `x-portal.*` (layout, header, footer, wizard, cards temáticos) ficam pendentes.

---

## 7. Changelog

| Data       | Descrição                                                                                                                                                                     |
| ---------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026-04-11 | Documento criado (Fase 3) — mapa das 21 telas do §14 + Portal Wizard + Área do Formando + Pages utilitárias; destacados 4 itens 🟡 a validar e 5 dependências 🔌 API-decision |
