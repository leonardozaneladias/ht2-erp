# Triagem Inspinia × Portal ArtFinal

> **Objetivo:** Cruzar cada view/componente/plugin do Inspinia v5.0 com as 21 telas do admin do PRD §14 (e os wizards do portal) para decidir **o que documentar e componentizar agora** versus **o que guardar para o futuro** versus **o que descartar**.
>
> **Princípio:** Nada é apagado. Itens fora do escopo imediato vão para o **Parking Lot** — ficam catalogados aqui com o caminho original no template, de modo que "futuro eu" ou qualquer dev novo no projeto saiba que existe e onde achar se precisar.
>
> **Insumo:** `docs/template/INSPINIA/README.md` (inventário Fase 1) + `docs/PRD_Sistema_Formatura_v3.1.0.md` §14 (21 telas do admin).

---

## Resumo Executivo

| Decisão                           |   Quantidade    | Significado                                                                     |
| --------------------------------- | :-------------: | ------------------------------------------------------------------------------- |
| ✅ **Vai usar**                   | ~62 componentes | Serão documentados e componentizados nas Ondas 1–6                              |
| 🅿️ **Parking Lot**                |    ~95 itens    | Preservados aqui como referência; podem ser promovidos a "vai usar" no futuro   |
| ❌ **Descartados**                |    ~30 itens    | Fora do escopo do domínio ArtFinal (ecommerce, projects/kanban genéricos, etc.) |
| ⚠️ **Itens ausentes do template** |        3        | Precisam de solução alternativa: TinyMCE, Tagify, Input Touchspin               |

**Resultado:** A Fase 2 passa de ~155 arquivos `.md` para **~62 arquivos** (redução de 60%). O Parking Lot não gera arquivos `.md` individuais — fica só como tabela consultável neste documento.

---

## Como usar este documento

1. **Iniciando um módulo novo?** Abra a seção "✅ Vai usar" e localize o componente pela categoria + nome
2. **Precisa de algo que não está na lista ativa?** Abra o **Parking Lot** — possivelmente o Inspinia tem mas foi deferido
3. **Evoluindo a plataforma com feature nova?** Verifique o Parking Lot antes de pedir para instalar plugin externo — a referência ao caminho original do Inspinia está aqui
4. **Vai descartar algo?** Confirme primeiro se não há solução alternativa prevista na coluna "Solução alternativa"

---

## 1. ✅ VAI USAR — Componentes Confirmados

Ordenado por **Onda de componentização** (ordem de criação do código Blade real).

### 1.1 Onda 1 — Layout Base (P0 — Sprint 15–16)

> Pré-requisito absoluto. Sem isto nada do admin renderiza.

| #   | Componente Inspinia                    | Caminho no template                                    | Uso no ArtFinal                               | Blade destino                                 |
| --- | -------------------------------------- | ------------------------------------------------------ | --------------------------------------------- | --------------------------------------------- |
| 1   | `shared/base.blade.php`                | `resources/views/shared/base.blade.php`                | Layout master do admin — wrapper `@extends`   | `resources/views/admin/layouts/app.blade.php` |
| 2   | `shared/vertical.blade.php`            | `resources/views/shared/vertical.blade.php`            | Orientation vertical (padrão)                 | (embutido no `app.blade.php`)                 |
| 3   | `shared/partials/sidenav.blade.php`    | `resources/views/shared/partials/sidenav.blade.php`    | Sidebar com menu 14.21                        | `x-admin.sidebar`                             |
| 4   | `shared/partials/topbar.blade.php`     | `resources/views/shared/partials/topbar.blade.php`     | Topbar com user dropdown + notif + breadcrumb | `x-admin.topbar`                              |
| 5   | `shared/partials/footer.blade.php`     | `resources/views/shared/partials/footer.blade.php`     | Rodapé simples                                | `x-admin.footer`                              |
| 6   | `shared/partials/page-title.blade.php` | `resources/views/shared/partials/page-title.blade.php` | Título da página + breadcrumb + ações         | `x-admin.page-header`                         |
| 7   | `shared/partials/head-css.blade.php`   | `resources/views/shared/partials/head-css.blade.php`   | `<head>` CSS inclusions                       | (inline no layout)                            |
| 8   | `shared/partials/title-meta.blade.php` | `resources/views/shared/partials/title-meta.blade.php` | `<title>` + meta tags                         | (inline no layout)                            |

**Config de skin:** `skin: default`, `sidenav-color: dark`, `topbar-color: light`, `sidenav-size: default`, `width: fluid`, `position: fixed`. Ver `docs/template/INSPINIA/README.md` §5.

### 1.2 Onda 2 — Data & Feedback (P1 — Sprint 15–19)

| #   | Componente Inspinia                                   | Caminho                                             | Uso no ArtFinal                                                          | Blade destino                             |
| --- | ----------------------------------------------------- | --------------------------------------------------- | ------------------------------------------------------------------------ | ----------------------------------------- |
| 9   | `ui/cards.blade.php`                                  | `resources/views/ui/cards.blade.php`                | Card genérico (14.2 dashboards, 14.12 tabs, 14.15 configs)               | `x-shared.card`                           |
| 10  | KPI card (extrair de `dashboard/analytics.blade.php`) | `resources/views/dashboard/analytics.blade.php`     | 4 KPIs do 14.2, totalizadores 14.12/14.13/14.17                          | `x-admin.kpi-card`                        |
| 11  | `ui/alerts.blade.php`                                 | `resources/views/ui/alerts.blade.php`               | Alertas do sistema 14.2 "Seção: Alertas"                                 | `x-shared.alert`                          |
| 12  | `ui/badges.blade.php`                                 | `resources/views/ui/badges.blade.php`               | Badges de status                                                         | `x-shared.badge`                          |
| 13  | Status badge (wrapper de Enum)                        | —                                                   | StatusParcela, StatusContrato, StatusAdesao, Adimplência                 | `x-shared.status-badge`                   |
| 14  | `ui/progress.blade.php`                               | `resources/views/ui/progress.blade.php`             | Meta de formandos 14.2 "% Atingido"                                      | `x-shared.progress-bar`                   |
| 15  | `ui/modals.blade.php`                                 | `resources/views/ui/modals.blade.php`               | Baixa manual, reemitir boleto, confirmações                              | `x-shared.modal`                          |
| 16  | `ui/offcanvas.blade.php`                              | `resources/views/ui/offcanvas.blade.php`            | Drawer lateral (form rápido 14.5, 14.18, filtros)                        | `x-admin.drawer`                          |
| 17  | `ui/buttons.blade.php`                                | `resources/views/ui/buttons.blade.php`              | Botões variantes primary/secondary/danger/ghost                          | `x-shared.button`                         |
| 18  | `plugins/loading-buttons.blade.php` (Ladda)           | `resources/views/plugins/loading-buttons.blade.php` | Botão "Entrar" 14.1, "Salvar" em todos os forms                          | `x-shared.loading-button`                 |
| 19  | `ui/notifications.blade.php`                          | `resources/views/ui/notifications.blade.php`        | Toasts de sucesso/erro                                                   | `x-shared.toast`                          |
| 20  | `plugins/sweet-alerts.blade.php`                      | `resources/views/plugins/sweet-alerts.blade.php`    | Confirmações destrutivas (inativar, cancelar parcela)                    | `x-shared.confirm-dialog`                 |
| 21  | `ui/tabs.blade.php`                                   | `resources/views/ui/tabs.blade.php`                 | 14.4 Contratos (5 tabs), 14.6 Produtos (5 tabs), 14.12 Formando (7 tabs) | `x-shared.tabs`                           |
| 22  | `ui/tooltips.blade.php`                               | `resources/views/ui/tooltips.blade.php`             | Tooltips explicativos 14.4, 14.6, 14.15                                  | `x-shared.tooltip` (talvez Alpine direto) |
| 23  | `ui/dropdowns.blade.php`                              | `resources/views/ui/dropdowns.blade.php`            | Dropdown user, ações por linha, menu                                     | `x-shared.dropdown`                       |
| 24  | `ui/accordions.blade.php`                             | `resources/views/ui/accordions.blade.php`           | 14.20 Cadastro Manual (8 sections), filtros avançados                    | `x-shared.accordion`                      |
| 25  | `ui/collapse.blade.php`                               | `resources/views/ui/collapse.blade.php`             | Filtros collapsable 14.12, 14.13                                         | `x-shared.collapse`                       |
| 26  | Empty state (extrair de páginas)                      | —                                                   | Listagens vazias ("Nenhum formando cadastrado")                          | `x-shared.empty-state`                    |
| 27  | `ui/list-group.blade.php`                             | `resources/views/ui/list-group.blade.php`           | Sidebar da ficha do formando 14.12                                       | `x-shared.list-group`                     |
| 28  | `ui/breadcrumb.blade.php`                             | `resources/views/ui/breadcrumb.blade.php`           | Breadcrumb no topbar                                                     | `x-shared.breadcrumb`                     |
| 29  | `ui/pagination.blade.php`                             | `resources/views/ui/pagination.blade.php`           | Paginação de listagens (override de `{{ $items->links() }}`)             | `x-shared.pagination`                     |
| 30  | `ui/spinners.blade.php`                               | `resources/views/ui/spinners.blade.php`             | Loaders genéricos (inline Livewire)                                      | `x-shared.spinner`                        |

### 1.3 Onda 3 — Forms & Tables (P2 — Sprint 17–19)

| #   | Componente Inspinia                            | Caminho                                                        | Uso no ArtFinal                                            | Blade destino                             |
| --- | ---------------------------------------------- | -------------------------------------------------------------- | ---------------------------------------------------------- | ----------------------------------------- |
| 31  | `form/elements.blade.php` (input text)         | `resources/views/form/elements.blade.php`                      | Todo formulário — base                                     | `x-shared.input`                          |
| 32  | `form/elements.blade.php` (textarea)           | idem                                                           | Descrições, observações                                    | `x-shared.textarea`                       |
| 33  | `form/elements.blade.php` (select nativo)      | idem                                                           | Selects simples (UF, mês, status)                          | `x-shared.select`                         |
| 34  | `form/select.blade.php` (Choices.js)           | `resources/views/form/select.blade.php`                        | Select searchable (Instituição, Contrato, Curso)           | `x-shared.select-search`                  |
| 35  | `form/pickers.blade.php` (Flatpickr)           | `resources/views/form/pickers.blade.php`                       | Datepicker (data início, data evento, vencimento)          | `x-shared.date-picker`                    |
| 36  | `form/pickers.blade.php` (Daterangepicker)     | idem                                                           | Range de datas (filtros 14.12, 14.13)                      | `x-shared.date-range-picker`              |
| 37  | `form/other-plugin.blade.php` (Inputmask CPF)  | `resources/views/form/other-plugin.blade.php`                  | Campo CPF em formandos/responsáveis                        | `x-shared.cpf-input`                      |
| 38  | idem (Inputmask CNPJ)                          | idem                                                           | Campo CNPJ 14.3 Instituições                               | `x-shared.cnpj-input`                     |
| 39  | idem (Inputmask CEP)                           | idem                                                           | CEP com busca ViaCEP 14.3                                  | `x-shared.cep-input`                      |
| 40  | idem (Inputmask telefone BR)                   | idem                                                           | Campo telefone                                             | `x-shared.phone-input`                    |
| 41  | idem (Inputmask monetário)                     | idem                                                           | Valores em reais (14.7 programações, 14.15)                | `x-shared.money-input`                    |
| 42  | `form/elements.blade.php` (toggle switch)      | `resources/views/form/elements.blade.php`                      | Toggle ativo, "exige responsável" 14.4                     | `x-shared.toggle`                         |
| 43  | `form/elements.blade.php` (checkbox)           | idem                                                           | Matriz ACL 14.19, checkbox lembrar-me 14.1                 | `x-shared.checkbox`                       |
| 44  | `form/elements.blade.php` (radio)              | idem                                                           | Opções exclusivas                                          | `x-shared.radio`                          |
| 45  | `form/elements.blade.php` (password)           | idem                                                           | Senha admin 14.1/14.18 com toggle visibility               | `x-shared.password-input`                 |
| 46  | `form/fileuploads.blade.php` (Dropzone)        | `resources/views/form/fileuploads.blade.php`                   | Upload logo 14.3, imagem produto 14.6, foto formando 14.12 | `x-shared.file-upload`                    |
| 47  | `form/validation.blade.php`                    | `resources/views/form/validation.blade.php`                    | Padrão de exibição de erros (Livewire)                     | (mixin no `x-shared.input`)               |
| 48  | `tables/datatables/basic.blade.php`            | `resources/views/tables/datatables/basic.blade.php`            | Todas as listagens admin                                   | `x-admin.data-table`                      |
| 49  | `tables/datatables/export-data.blade.php`      | `resources/views/tables/datatables/export-data.blade.php`      | Export CSV/Excel 14.3, 14.13, 14.17                        | embutido em `x-admin.data-table` via prop |
| 50  | `tables/datatables/checkbox-select.blade.php`  | `resources/views/tables/datatables/checkbox-select.blade.php`  | Seleção múltipla 14.13 "Dar Baixa em Lote"                 | embutido via prop                         |
| 51  | `tables/datatables/column-searching.blade.php` | `resources/views/tables/datatables/column-searching.blade.php` | Filtros por coluna 14.3, 14.4, 14.6, 14.12                 | embutido via prop                         |
| 52  | `tables/datatables/range-search.blade.php`     | `resources/views/tables/datatables/range-search.blade.php`     | Filtros de data range 14.12, 14.13                         | embutido via prop                         |
| 53  | `tables/static.blade.php`                      | `resources/views/tables/static.blade.php`                      | Tabelas simples sem JS (subtelas inline, ficha formando)   | `x-shared.static-table`                   |
| 54  | `tables/custom.blade.php`                      | `resources/views/tables/custom.blade.php`                      | Tabela timeline de programações 14.7                       | `x-admin.timeline-table`                  |

> **Observação sobre DataTables:** Em vez de criar N componentes Blade separados por variante, criaremos **um único** `x-admin.data-table` com props (`:searchable`, `:exportable`, `:selectable`, `:columnSearch`, `:dateRange`) que alternam cada recurso. As variantes do Inspinia servem como referência HTML/JS.

### 1.4 Onda 4 — Charts & Dashboards (P3 — Sprint 20–23)

Apenas **4 tipos de gráfico** são realmente necessários para os 2 gráficos do 14.2 + relatórios 14.17. O restante vai para o Parking Lot.

| #   | Componente Inspinia             | Caminho                                         | Uso no ArtFinal                                                                             | Blade destino          |
| --- | ------------------------------- | ----------------------------------------------- | ------------------------------------------------------------------------------------------- | ---------------------- |
| 55  | `charts/apex/bar.blade.php`     | `resources/views/charts/apex/bar.blade.php`     | 14.2 "Adesões por Mês"                                                                      | `x-admin.chart-bar`    |
| 56  | `charts/apex/line.blade.php`    | `resources/views/charts/apex/line.blade.php`    | 14.2 "Receita x Inadimplência" (line dual)                                                  | `x-admin.chart-line`   |
| 57  | `charts/apex/column.blade.php`  | `resources/views/charts/apex/column.blade.php`  | 14.17 Receita por contrato                                                                  | `x-admin.chart-column` |
| 58  | `charts/apex/pie.blade.php`     | `resources/views/charts/apex/pie.blade.php`     | 14.17 distribuição por modalidade de pagamento                                              | `x-admin.chart-pie`    |
| 59  | `dashboard/analytics.blade.php` | `resources/views/dashboard/analytics.blade.php` | **Referência** de layout do 14.2 (não vira componente — só serve de inspiração para a view) | (view)                 |

**Chart wrapper genérico:** Criar `x-admin.chart-card` com slot para canvas e props (`:title`, `:filters`, `:height`) para padronizar a moldura dos 4 gráficos.

### 1.5 Onda 5 — Pages (P4 — Sprint 15–24)

| #   | Componente Inspinia                | Caminho                                            | Uso no ArtFinal                               | Blade destino                                                       |
| --- | ---------------------------------- | -------------------------------------------------- | --------------------------------------------- | ------------------------------------------------------------------- |
| 60  | `auth-split/sign-in.blade.php`     | `resources/views/auth-split/sign-in.blade.php`     | 14.1 Login Admin                              | `resources/views/admin/auth/login.blade.php` (view, não componente) |
| 61  | `error/404.blade.php`              | `resources/views/error/404.blade.php`              | Página 404 customizada                        | `resources/views/errors/404.blade.php`                              |
| 62  | `error/500.blade.php`              | `resources/views/error/500.blade.php`              | Página 500 customizada                        | `resources/views/errors/500.blade.php`                              |
| 63  | `error/403.blade.php`              | `resources/views/error/403.blade.php`              | Acesso negado (ACL)                           | `resources/views/errors/403.blade.php`                              |
| 64  | `error/maintenance.blade.php`      | `resources/views/error/maintenance.blade.php`      | Modo manutenção                               | `resources/views/errors/503.blade.php`                              |
| 65  | `pages/account-settings.blade.php` | `resources/views/pages/account-settings.blade.php` | Perfil do admin logado (alterar senha, dados) | `resources/views/admin/account/settings.blade.php`                  |

### 1.6 Onda 6 — Plugins (P5 — Sprint 24+)

| #   | Componente Inspinia                       | Caminho                                        | Uso no ArtFinal                          | Blade destino                           |
| --- | ----------------------------------------- | ---------------------------------------------- | ---------------------------------------- | --------------------------------------- |
| 66  | `plugins/sortable.blade.php` (SortableJS) | `resources/views/plugins/sortable.blade.php`   | 14.10 drag-and-drop de termos do produto | integração em `x-admin.sortable-list`   |
| 67  | `plugins/clipboard.blade.php`             | `resources/views/plugins/clipboard.blade.php`  | Copiar CPF, código turma, link de adesão | trigger Alpine direto (sem componente)  |
| 68  | `plugins/pass-meter.blade.php`            | `resources/views/plugins/pass-meter.blade.php` | Medidor de força de senha 14.18          | integração em `x-shared.password-input` |

---

## 2. 🅿️ PARKING LOT — Preservados para Futuro

> **Regra:** Nenhum destes é componentizado agora, mas **todos ficam catalogados com o caminho original do template**. Se no futuro alguém precisar (ex: implementar Vote List para votar em fornecedores, ou Chat para atendimento ao formando), basta abrir esta tabela, copiar o caminho e começar dali.

### 2.1 Apps com potencial de uso futuro

| App Inspinia                                                | Caminho                                                                                                              | Cenário hipotético no ArtFinal                                                                                                                   | Prioridade futura |
| ----------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ | :---------------: |
| **Vote List**                                               | `apps/vote-list.blade.php`                                                                                           | Votação de fornecedores (fotógrafo, buffet, DJ) pelos formandos; pesquisa de satisfação; votação de tema da festa                                |     🟡 Médio      |
| **Forum (view + post)**                                     | `apps/forum/view.blade.php`, `apps/forum/post.blade.php`                                                             | Espaço de discussão entre formandos e coordenação de cada turma                                                                                  |     🟡 Médio      |
| **Chat**                                                    | `apps/chat.blade.php`                                                                                                | Chat de atendimento ao formando (suporte comercial) ou chat interno da equipe                                                                    |      🟢 Alto      |
| **Calendar** (FullCalendar)                                 | `apps/calendar.blade.php`                                                                                            | Agenda de eventos de formatura (colação, missa, festa, ensaio), lembretes de vencimento                                                          |      🟢 Alto      |
| **Email (inbox/compose/details)**                           | `apps/email/inbox.blade.php`, `compose.blade.php`, `details.blade.php`                                               | Inbox interno de atendimento (ticket-like), preview de e-mails enviados ao formando                                                              |     🟡 Médio      |
| **Social Feed**                                             | `apps/social-feed.blade.php`                                                                                         | Feed interno tipo "novidades" para a equipe admin                                                                                                |     🔴 Baixo      |
| **Blog (grid/list/article/add)**                            | `apps/blog/grid.blade.php`, `list.blade.php`, `article.blade.php`, `add.blade.php`                                   | Blog público da empresa ArtFinal com notícias para captação                                                                                      |     🟡 Médio      |
| **Pin Board**                                               | `apps/pin-board.blade.php`                                                                                           | Mural de avisos/notas para a equipe                                                                                                              |     🔴 Baixo      |
| **File Manager**                                            | `apps/file-manager.blade.php`                                                                                        | Gestão de documentos anexados por formando (comprovantes, contratos assinados)                                                                   |      🟢 Alto      |
| **Outlook view**                                            | `apps/outlook.blade.php`                                                                                             | Visualização estilo Outlook do inbox de atendimento (alternativa ao Email app)                                                                   |     🔴 Baixo      |
| **Companies list**                                          | `apps/companies.blade.php`                                                                                           | Gestão de fornecedores (buffet, fotógrafo, DJ, formatura house)                                                                                  |     🟡 Médio      |
| **Clients**                                                 | `apps/clients.blade.php`                                                                                             | Gestão de clientes B2B (instituições em prospecção)                                                                                              |     🟡 Médio      |
| **Issue Tracker**                                           | `apps/issue-tracker.blade.php`                                                                                       | Bug tracking interno / pedidos de ajustes dos clientes                                                                                           |     🔴 Baixo      |
| **API Keys**                                                | `apps/api-keys.blade.php`                                                                                            | Gestão de chaves de API se o sistema expuser integrações (parceiros)                                                                             |     🔴 Baixo      |
| **Projects (list/grid/details/activity/kanban/team-board)** | `apps/projects/*.blade.php`                                                                                          | Kanban genérico não aplica. **Potencial adaptação:** cada contrato como "projeto" com tarefas de produção.                                       |     🔴 Baixo      |
| **Users (contacts/roles/permissions/role-details)**         | `apps/users/*.blade.php`                                                                                             | Referência visual para 14.18/14.19 (CRUD de usuários admin e matriz ACL). **Já é usado como referência nas Ondas 2–3**, mas não vira componente. |     🟡 Médio      |
| **Ecommerce Marketplace/Products/Grid/Details**             | `apps/ecommerce/marketplace.blade.php`, `products.blade.php`, `products-grid.blade.php`, `product-details.blade.php` | Catálogo público de pacotes/produtos se a empresa quiser vitrine no site                                                                         |     🟡 Médio      |
| **Ecommerce Customers**                                     | `apps/ecommerce/customers.blade.php`                                                                                 | Layout alternativo para 14.12 (lista de formandos estilo customer list)                                                                          |     🔴 Baixo      |
| **Ecommerce Reviews**                                       | `apps/ecommerce/reviews.blade.php`                                                                                   | Avaliações de formandos sobre a empresa                                                                                                          |      🟢 Alto      |

### 2.2 UI Components secundários

| Componente                  | Caminho                                     | Cenário                                                        | Motivo do parking                                                    |
| --------------------------- | ------------------------------------------- | -------------------------------------------------------------- | -------------------------------------------------------------------- |
| `ui/carousel.blade.php`     | `resources/views/ui/carousel.blade.php`     | Galeria de fotos de formaturas anteriores, banner home do site | Nenhuma tela do 14.x usa carousel                                    |
| `ui/popovers.blade.php`     | `resources/views/ui/popovers.blade.php`     | Tooltips ricos com conteúdo HTML                               | Tooltip simples já cobre os casos atuais                             |
| `ui/scrollspy.blade.php`    | `resources/views/ui/scrollspy.blade.php`    | Landing page / doc pública                                     | Admin não tem scrollspy                                              |
| `ui/placeholders.blade.php` | `resources/views/ui/placeholders.blade.php` | Skeleton loading                                               | Livewire já tem `wire:loading`; pode ser útil para listagens pesadas |
| `ui/typography.blade.php`   | `resources/views/ui/typography.blade.php`   | Tipografia de showcase                                         | É showcase, não é componente                                         |
| `ui/colors.blade.php`       | `resources/views/ui/colors.blade.php`       | Palette de cores                                               | Referência apenas                                                    |
| `ui/images.blade.php`       | `resources/views/ui/images.blade.php`       | Wrappers de imagem (responsive, lazy)                          | Tailwind cobre nativo                                                |
| `ui/links.blade.php`        | `resources/views/ui/links.blade.php`        | Estilos de links                                               | Tailwind cobre                                                       |
| `ui/utilities.blade.php`    | `resources/views/ui/utilities.blade.php`    | Classes utilitárias                                            | Tailwind já é isso                                                   |
| `ui/videos.blade.php`       | `resources/views/ui/videos.blade.php`       | Wrappers de vídeo embed                                        | Admin não tem vídeo                                                  |

### 2.3 Forms & inputs avançados

| Componente                                   | Caminho                                       | Cenário                                                                                  | Motivo                                                 |
| -------------------------------------------- | --------------------------------------------- | ---------------------------------------------------------------------------------------- | ------------------------------------------------------ |
| `form/text-editors.blade.php` (Quill)        | `resources/views/form/text-editors.blade.php` | **Substituto do TinyMCE no 14.11 se quisermos evitar dependência paga** — Quill é grátis | Decisão pendente: TinyMCE (paga) vs Quill              |
| `form/wizard.blade.php`                      | `resources/views/form/wizard.blade.php`       | **Wizard do portal de adesão (7 etapas)** — uso confirmado no portal, não no admin       | Onda 3 do **portal**, não do admin                     |
| `form/layout.blade.php`                      | `resources/views/form/layout.blade.php`       | Layouts de form (horizontal, inline)                                                     | Formulários do admin usam layout vertical padrão       |
| `form/range-slider.blade.php` (noUiSlider)   | `resources/views/form/range-slider.blade.php` | Simulador de parcelamento 14.14 — slider "Número de Parcelas"                            | Pode ser number input simples; slider é "nice to have" |
| `form/pickers.blade.php` (Pickr colorpicker) | `resources/views/form/pickers.blade.php`      | Customização de tema do sistema (cores da empresa)                                       | Features de customização visual futuras                |
| `form/fileuploads.blade.php` (FilePond)      | `resources/views/form/fileuploads.blade.php`  | Alternativa mais moderna ao Dropzone                                                     | Dropzone atende                                        |
| `form/other-plugin.blade.php` (Select2)      | `resources/views/form/other-plugin.blade.php` | Alternativa ao Choices.js                                                                | Choices.js é mais leve (sem jQuery)                    |
| `form/other-plugin.blade.php` (Typeahead)    | idem                                          | Autocomplete em campos livres                                                            | Choices.js searchable atende                           |

### 2.4 DataTables variantes não usadas agora

| Variante                                   | Caminho                                                     | Cenário futuro                                                                        |
| ------------------------------------------ | ----------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| `datatables/ajax.blade.php`                | `resources/views/tables/datatables/ajax.blade.php`          | Se listagens do admin ficarem muito pesadas (>10k registros), migrar para server-side |
| `datatables/child-rows.blade.php`          | `resources/views/tables/datatables/child-rows.blade.php`    | Expandir linha da parcela para mostrar histórico de tentativas de pagamento           |
| `datatables/fixed-columns.blade.php`       | `resources/views/tables/datatables/fixed-columns.blade.php` | Tabelas muito largas (mais de 10 colunas)                                             |
| `datatables/fixed-header.blade.php`        | `resources/views/tables/datatables/fixed-header.blade.php`  | Listagens com scroll longo                                                            |
| `datatables/scroll.blade.php`              | `resources/views/tables/datatables/scroll.blade.php`        | Tabelas com scroll interno                                                            |
| `datatables/columns.blade.php` (show/hide) | `resources/views/tables/datatables/columns.blade.php`       | Permitir user customizar colunas visíveis                                             |
| `datatables/javascript.blade.php`          | `resources/views/tables/datatables/javascript.blade.php`    | Data source JS puro (sem servidor)                                                    |
| `datatables/rendering.blade.php`           | `resources/views/tables/datatables/rendering.blade.php`     | Renderização customizada de células (HTML)                                            |
| `datatables/rows-add.blade.php`            | `resources/views/tables/datatables/rows-add.blade.php`      | Adicionar linha inline (subtelas como programações)                                   |
| `datatables/select.blade.php`              | `resources/views/tables/datatables/select.blade.php`        | Seleção de linhas sem checkbox (click na linha)                                       |

### 2.5 Charts não usados agora

**ApexCharts (16 de 20 no parking):**

| Tipo        | Caminho                             | Cenário futuro                                           |
| ----------- | ----------------------------------- | -------------------------------------------------------- |
| area        | `charts/apex/area.blade.php`        | Evolução temporal preenchida (receita acumulada)         |
| mixed       | `charts/apex/mixed.blade.php`       | Bar + Line combinados (receita vs meta)                  |
| radar       | `charts/apex/radar.blade.php`       | Performance multi-dimensional de um contrato             |
| radialbar   | `charts/apex/radialbar.blade.php`   | % meta atingida em formato radial                        |
| heatmap     | `charts/apex/heatmap.blade.php`     | Heatmap de dias da semana mais inadimplentes             |
| candlestick | `charts/apex/candlestick.blade.php` | Variação de preço (não se aplica)                        |
| bubble      | `charts/apex/bubble.blade.php`      | Dispersão com 3 dimensões                                |
| boxplot     | `charts/apex/boxplot.blade.php`     | Distribuição estatística (ex: valores por contrato)      |
| funnel      | `charts/apex/funnel.blade.php`      | **Funil de conversão do wizard de adesão** (etapa 1 → 7) |
| slope       | `charts/apex/slope.blade.php`       | Comparação ano a ano                                     |
| treemap     | `charts/apex/treemap.blade.php`     | Proporção de receita por contrato/pacote                 |
| polar-area  | `charts/apex/polar-area.blade.php`  | Alternativa a pie                                        |
| range       | `charts/apex/range.blade.php`       | Ranges temporais                                         |
| scatter     | `charts/apex/scatter.blade.php`     | Dispersão                                                |
| sparklines  | `charts/apex/sparklines.blade.php`  | Mini-gráficos dentro de cards (KPI card com tendência)   |
| timeline    | `charts/apex/timeline.blade.php`    | Timeline de eventos de um contrato                       |

**ECharts (todos 11 no parking):** `charts/echart/area.blade.php`, `bar.blade.php`, `candlestick.blade.php`, `gauge.blade.php`, `geo-map.blade.php`, `heatmap.blade.php`, `line.blade.php`, `other.blade.php`, `pie.blade.php`, `radar.blade.php`, `scatter.blade.php`. Cenário futuro: substituto do ApexCharts se precisarmos de gráficos mais sofisticados (geo-map do Brasil com formandos por estado, gauge de meta atingida).

### 2.6 Maps

| Mapa                 | Caminho                                | Cenário futuro                                                                                    |
| -------------------- | -------------------------------------- | ------------------------------------------------------------------------------------------------- |
| Leaflet              | `maps/leaflet.blade.php`               | Mapa interativo dos eventos de formatura, endereço da instituição                                 |
| Vector (jsVectorMap) | `maps/vector.blade.php`                | Mapa do Brasil com heatmap de formandos por estado (relatório geográfico)                         |
| Google               | `maps/google.blade.php` ⚠️ SDK AUSENTE | Se houver integração futura com Google Places (autocompletar endereço) — **precisa instalar SDK** |

### 2.7 Pages não usadas agora

| Page                                                    | Caminho                                            | Cenário futuro                                                                                          |
| ------------------------------------------------------- | -------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| `auth/*` (estilo Basic)                                 | `resources/views/auth/*.blade.php`                 | Alternativa visual ao auth-split                                                                        |
| `auth-card/*`                                           | `resources/views/auth-card/*.blade.php`            | Alternativa visual ao auth-split                                                                        |
| `auth-*/sign-up.blade.php`                              | —                                                  | Cadastro de admin por auto-serviço (não há no PRD)                                                      |
| `auth-*/reset-pass.blade.php`, `new-pass.blade.php`     | —                                                  | **Vão ser usados**: 14.1 tem "Esqueci minha senha" — ✅ promover para Onda 5 quando houver fluxo        |
| `auth-*/two-factor.blade.php`                           | —                                                  | 2FA para admins (segurança futura)                                                                      |
| `auth-*/lock-screen.blade.php`                          | —                                                  | Lock screen após inatividade                                                                            |
| `auth-*/login-pin.blade.php`                            | —                                                  | Login com PIN (não aplica)                                                                              |
| `auth-*/success-mail.blade.php`                         | —                                                  | Confirmação de envio de e-mail                                                                          |
| `auth-*/delete-account.blade.php`                       | —                                                  | Self-service de deleção (não aplica a admin)                                                            |
| `error/400.blade.php`, `401.blade.php`, `408.blade.php` | `resources/views/error/*.blade.php`                | Usar se aparecerem erros específicos                                                                    |
| `pages/profile.blade.php`                               | `resources/views/pages/profile.blade.php`          | Perfil público do admin (diferente de settings)                                                         |
| `pages/faq.blade.php`                                   | `resources/views/pages/faq.blade.php`              | Página de ajuda do admin / FAQ público                                                                  |
| `pages/pricing.blade.php`                               | `resources/views/pages/pricing.blade.php`          | Planos/pricing do site público ArtFinal                                                                 |
| `pages/timeline.blade.php`                              | `resources/views/pages/timeline.blade.php`         | **Usar como referência** para a aba 14.12 Tab 7 (Histórico/Auditoria) — não vira componente mas inspira |
| `pages/search-results.blade.php`                        | `resources/views/pages/search-results.blade.php`   | Busca global do admin                                                                                   |
| `pages/coming-soon.blade.php`                           | `resources/views/pages/coming-soon.blade.php`      | Páginas em desenvolvimento                                                                              |
| `pages/terms-conditions.blade.php`                      | `resources/views/pages/terms-conditions.blade.php` | Termos de uso do portal público                                                                         |
| `pages/privacy-policy.blade.php`                        | `resources/views/pages/privacy-policy.blade.php`   | Política de privacidade (LGPD)                                                                          |
| `pages/gallery.blade.php`                               | `resources/views/pages/gallery.blade.php`          | Galeria de formaturas (site público)                                                                    |
| `pages/sitemap.blade.php`                               | `resources/views/pages/sitemap.blade.php`          | Mapa do site                                                                                            |
| `pages/empty.blade.php`                                 | `resources/views/pages/empty.blade.php`            | Starter page (já temos a nossa)                                                                         |

### 2.8 Plugins não usados agora

| Plugin                                      | Caminho                                          | Cenário futuro                                                                      |
| ------------------------------------------- | ------------------------------------------------ | ----------------------------------------------------------------------------------- |
| `plugins/tour.blade.php` (TourGuideJS)      | `resources/views/plugins/tour.blade.php`         | Onboarding guiado para novos admins                                                 |
| `plugins/animation.blade.php` (animate.css) | `resources/views/plugins/animation.blade.php`    | Transições de página, alertas                                                       |
| `plugins/masonry.blade.php`                 | `resources/views/plugins/masonry.blade.php`      | Layout masonry para galeria de fotos                                                |
| `plugins/idle-timer.blade.php`              | `resources/views/plugins/idle-timer.blade.php`   | Auto-logout após inatividade (segurança)                                            |
| `plugins/live-favicon.blade.php` (Tinycon)  | `resources/views/plugins/live-favicon.blade.php` | Badge no favicon com contador de notificações pendentes                             |
| `plugins/text-diff.blade.php` (diff)        | `resources/views/plugins/text-diff.blade.php`    | **Comparar versões de termos 14.11** — **potencial alto**                           |
| `plugins/i18.blade.php`                     | `resources/views/plugins/i18.blade.php`          | Multi-idioma se expandir para outros países                                         |
| `plugins/tree-view.blade.php` (jstree)      | `resources/views/plugins/tree-view.blade.php`    | Árvore de permissões hierárquicas 14.19 (alternativa visual à matriz)               |
| `plugins/video-player.blade.php` (Plyr)     | `resources/views/plugins/video-player.blade.php` | Tutoriais em vídeo no admin                                                         |
| `plugins/pdf-viewer.blade.php` (pdf.js)     | `resources/views/plugins/pdf-viewer.blade.php`   | **Preview inline de PDFs gerados** (termos consolidados 14.10) — **potencial alto** |

### 2.9 Icons & Flags

| Recurso                  | Caminho                                  | Cenário futuro                                      |
| ------------------------ | ---------------------------------------- | --------------------------------------------------- |
| `icons/flags.blade.php`  | `resources/views/icons/flags.blade.php`  | Multi-idioma, exibição de país                      |
| `icons/lucide.blade.php` | `resources/views/icons/lucide.blade.php` | Alternativa ao Tabler (Tabler é o default do admin) |

### 2.10 Layouts alternativos

| Layout                                      | Caminho                                                     | Cenário futuro                                      |
| ------------------------------------------- | ----------------------------------------------------------- | --------------------------------------------------- |
| `layouts/horizontal.blade.php`              | `resources/views/layouts/horizontal.blade.php`              | Se quiser experimentar topbar nav em vez de sidebar |
| `layouts/boxed.blade.php`                   | `resources/views/layouts/boxed.blade.php`                   | Layout boxed (visual "cartão" com margem lateral)   |
| `layouts/compact.blade.php`                 | `resources/views/layouts/compact.blade.php`                 | Sidebar colapsada por padrão                        |
| `layouts/scrollable.blade.php`              | `resources/views/layouts/scrollable.blade.php`              | Topbar e sidebar scrollable (não fixed)             |
| `layouts/preloader.blade.php`               | `resources/views/layouts/preloader.blade.php`               | Preloader de carregamento da página                 |
| `layouts/sidebar/compact.blade.php`         | `resources/views/layouts/sidebar/compact.blade.php`         | Sidebar tamanho compact                             |
| `layouts/sidebar/gradient.blade.php`        | `resources/views/layouts/sidebar/gradient.blade.php`        | Sidebar com gradient                                |
| `layouts/sidebar/gray.blade.php`            | `resources/views/layouts/sidebar/gray.blade.php`            | Sidebar cinza                                       |
| `layouts/sidebar/image.blade.php`           | `resources/views/layouts/sidebar/image.blade.php`           | Sidebar com imagem de fundo                         |
| `layouts/sidebar/light.blade.php`           | `resources/views/layouts/sidebar/light.blade.php`           | Sidebar claro                                       |
| `layouts/sidebar/offcanvas.blade.php`       | `resources/views/layouts/sidebar/offcanvas.blade.php`       | Sidebar offcanvas (mobile-like)                     |
| `layouts/sidebar/on-hover.blade.php`        | `resources/views/layouts/sidebar/on-hover.blade.php`        | Sidebar expande ao passar o mouse                   |
| `layouts/sidebar/on-hover-active.blade.php` | `resources/views/layouts/sidebar/on-hover-active.blade.php` | Variação do on-hover                                |
| `layouts/sidebar/with-lines.blade.php`      | `resources/views/layouts/sidebar/with-lines.blade.php`      | Sidebar com separadores de linha                    |
| `layouts/topbar/dark.blade.php`             | `resources/views/layouts/topbar/dark.blade.php`             | Topbar escura                                       |
| `layouts/topbar/gradient.blade.php`         | `resources/views/layouts/topbar/gradient.blade.php`         | Topbar gradient                                     |
| `layouts/topbar/gray.blade.php`             | `resources/views/layouts/topbar/gray.blade.php`             | Topbar cinza                                        |

---

## 3. ❌ DESCARTADOS — Fora do escopo

> Itens que **não fazem sentido no domínio ArtFinal** mesmo em expansões futuras razoáveis.

| Item                                                                                                                                                                                                                                                                                                             | Motivo                                                                                                                                               |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| `apps/ecommerce/cart.blade.php`                                                                                                                                                                                                                                                                                  | Carrinho de compras — o portal do formando usa wizard, não carrinho                                                                                  |
| `apps/ecommerce/checkout.blade.php`                                                                                                                                                                                                                                                                              | Checkout estilo e-commerce — wizard do portal é o equivalente                                                                                        |
| `apps/ecommerce/orders.blade.php`, `order-add.blade.php`, `order-details.blade.php`, `purchased-orders.blade.php`                                                                                                                                                                                                | Pedidos de e-commerce — não é o modelo do ArtFinal                                                                                                   |
| `apps/ecommerce/sellers.blade.php`, `seller-details.blade.php`                                                                                                                                                                                                                                                   | Marketplace multi-vendedor — não aplica                                                                                                              |
| `apps/ecommerce/product-stocks.blade.php`, `warehouse.blade.php`, `product-add.blade.php`, `product-views.blade.php`, `product-details.blade.php`, `products.blade.php`, `products-grid.blade.php`, `attributes.blade.php`, `categories.blade.php`, `settings.blade.php`, `refunds.blade.php`, `sales.blade.php` | Gestão de estoque/catálogo físico — pacotes de formatura não têm estoque                                                                             |
| `apps/ecommerce/marketplace.blade.php`                                                                                                                                                                                                                                                                           | Marketplace multi-seller                                                                                                                             |
| `apps/users/permissions.blade.php`, `role-details.blade.php`, `roles.blade.php`, `contacts.blade.php`                                                                                                                                                                                                            | A UI de ACL do ArtFinal vai usar **Spatie Permissions** diretamente — o layout do Inspinia serve só como referência visual, não precisamos das views |
| `apps/projects/activity.blade.php`, `details.blade.php`, `grid.blade.php`, `kanban.blade.php`, `list.blade.php`, `team-board.blade.php`                                                                                                                                                                          | Projects/Kanban genérico — não é um PM tool, não faz sentido adaptar                                                                                 |
| `apps/manage.blade.php`                                                                                                                                                                                                                                                                                          | Página misc do template sem aplicação clara                                                                                                          |
| `apps/invoice/create.blade.php`, `details.blade.php`, `list.blade.php`                                                                                                                                                                                                                                           | Sistema de faturamento genérico — ArtFinal já tem lógica própria de parcelas e boletos; não reusa esse modelo                                        |
| `icons/flags.blade.php` (uso direto)                                                                                                                                                                                                                                                                             | Sistema é BR-only por ora (ver parking lot se internacionalizar)                                                                                     |
| `maps/google.blade.php` (uso direto)                                                                                                                                                                                                                                                                             | SDK ausente, Leaflet ou jsvectormap substituem                                                                                                       |
| `index.blade.php` (home do Inspinia)                                                                                                                                                                                                                                                                             | Demo showcase — irrelevante                                                                                                                          |

---

## 4. ⚠️ Itens da Spec do Prompt Que NÃO Existem no Inspinia

Estes itens foram listados nas specs do prompt `PROMPT-ANALISE-INSPINIA.md` mas **não estão no template Tailwind v5.0**. Precisam de solução alternativa:

| Item ausente                 | Uso esperado no ArtFinal                                                          | Solução alternativa                                                                                                                                                                    |
| ---------------------------- | --------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **TinyMCE** (editor WYSIWYG) | 14.11 Gestão de Termos — editor com barra de variáveis                            | **Opção A:** Usar Quill (já no package.json) + plugin de variables — grátis.<br>**Opção B:** Instalar TinyMCE separado (self-hosted grátis ou cloud API key). **Recomendação: Quill.** |
| **Tagify** (tags input)      | 14.15 Configurações Globais — "Dias de Vencimento" e "Dias de Lembrete" como tags | **Opção A:** Choices.js em modo `multiple` — já instalado, basta configurar.<br>**Opção B:** Componente Alpine.js custom com chips.<br>**Recomendação: Choices.js multiple.**          |
| **Input Touchspin**          | Não é necessário — o PRD usa number inputs normais                                | Ignorar — `<input type="number">` atende                                                                                                                                               |

Decisão sobre TinyMCE/Quill será registrada na Fase 4 (CONVENTIONS.md) antes de implementar 14.11.

---

## 5. Estatísticas Finais

### 5.1 Contagem por decisão

```
✅ VAI USAR:      ~62 componentes/views
🅿️ PARKING LOT: ~95 itens catalogados
❌ DESCARTADOS:  ~30 itens
─────────────────────────────
Total inventariado: ~187 itens distintos
Arquivos .blade.php do Inspinia: 240 (inclui partials, 3 estilos de auth, 9 variants de sidebar, etc.)
```

### 5.2 Redução de escopo da Fase 2

|       Antes da triagem        |   Depois da triagem    |        Redução         |
| :---------------------------: | :--------------------: | :--------------------: |
| ~155 arquivos `.md` estimados | **~62 arquivos `.md`** | **60% menos trabalho** |

### 5.3 Cobertura por tela do PRD

As 21 telas do admin (§14) + os 2 wizards do portal são 100% atendidas pelos 62 componentes da seção "Vai Usar". Nenhum gap identificado — a única exceção é o editor rich text (TinyMCE ausente), resolvida pela alternativa Quill.

---

## 6. Próxima Ação

**Fase 2 — Documentação granular (por ondas):**

Com base em "1. ✅ VAI USAR", criar os ~62 arquivos `.md` em `docs/template/INSPINIA/[Categoria]/[nome].md`, na ordem:

1. **Onda 1 (8 arquivos)** — Layouts + Navigation (P0, pré-requisito de tudo)
2. **Onda 2 (22 arquivos)** — Data + Feedback + UI base
3. **Onda 3 (24 arquivos)** — Forms + Tables
4. **Onda 4 (5 arquivos)** — Charts + Dashboards
5. **Onda 5 (6 arquivos)** — Pages (auth split, errors, account settings)
6. **Onda 6 (3 arquivos)** — Plugins (sortable, clipboard, pass-meter)

Reportar ao fim de cada onda com contagem `X/Y arquivos criados`.

---

## Changelog

| Data       | Descrição                                                                                                                                                                         |
| ---------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026-04-11 | Documento criado — Triagem Fase 5 adiantada, antes da Fase 2 para reduzir escopo de ~155 para ~62 componentes; parking lot completo catalogado com caminhos originais do template |
