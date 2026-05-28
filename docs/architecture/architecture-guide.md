# Guia de Arquitetura e Organização do Projeto

**Projeto:** Sistema de Gerenciamento de Formaturas (Portal ArtFinal)  
**Versão:** 1.0.0  
**Data:** 09/04/2026  
**Responsável:** Leonardo — HT2ML TECH LTDA  
**Stack:** Laravel 13 · PostgreSQL 16 · Tailwind CSS 4 · Livewire 3 · Inspinia Template

---

## 1. Filosofia do Projeto

> **⚠️ Nota sobre Templates:** O PRD v3.0 referencia "Metronic" como template do admin.
> Esta decisão foi atualizada para **Inspinia Multipurpose Admin Dashboard (Tailwind CSS 4)**.
> Todas as referências a componentes "Metronic" no PRD devem ser lidas como equivalentes
> no Inspinia (DataTables, Modals, Tabs, Cards, Drawers, etc.).
>
> Para o Portal do Formando, o PRD sugere Preline UI. A decisão final está entre
> **Preline UI** ou **Tailwind puro** — a ser definida antes da Sprint 4.

Este projeto adota a filosofia **"Convention Over Configuration"** do Laravel, estendida com padrões explícitos para manter clareza em um sistema de médio-grande porte (31+ tabelas, dois frontends independentes, integração com gateway bancário).

**Princípios norteadores:**

- **Separação clara de domínios** — Admin e Portal são mundos distintos com guards, layouts, rotas, controllers e middleware próprios
- **Services como coração da lógica** — Controllers magros, Services gordos. Toda regra de negócio vive em Services
- **Imutabilidade e Snapshots** — Dados comerciais "fotografados" no momento da adesão, nunca referenciados dinamicamente
- **Append-only para auditoria** — Logs nunca são editados ou deletados
- **Componentização Blade** — Todo elemento visual reutilizável vira um componente Blade, alimentado pelo Inspinia
- **Documentação viva** — Cada módulo tem sua própria documentação que evolui junto com o código

---

## 2. Estrutura de Pastas do Projeto

```
portalartfinal_v2/
│
├── .docs/                              ← 📚 Documentação do projeto (versionada)
│   ├── PRD_v3.0.md                     ← PRD principal (nota: menciona Metronic → ler como Inspinia)
│   ├── ARCHITECTURE-GUIDE.md           ← Este documento
│   ├── CONVENTIONS.md                  ← Padrões de código, commit, cache, erros, performance
│   ├── TEMPLATE-MAP-AND-COMPONENTS.md  ← Mapeamento do Inspinia + catálogo de componentes Blade
│   ├── TOOLS-AND-PACKAGES.md           ← Pacotes, plugins, ferramentas e justificativas
│   ├── PROMPTS-AND-MEMORY.md           ← Prompts para IA, CLAUDE.md, checklists de sprint
│   ├── PADRONIZACAO-SPRINTS-AGENTES.md ← Pint, Prettier, ESLint, Husky, quebra de sprints, agentes IA
│   ├── PLANE-GUIDE.md                  ← Guia completo do Plane + MCP (ferramenta principal)
│   ├── MIGRATION-LINEAR-TO-PLANE.md    ← Guia de migração Linear → Plane
│   ├── LINEAR-GUIDE.md                 ← Guia do Linear (referência histórica, Sprint 0)
│   ├── INFRA.md                        ← Infraestrutura Laradock / Docker
│   ├── INSPINIA-ANALISE.md             ← Análise detalhada do template Inspinia
│   ├── CHANGELOG.md                    ← Histórico de mudanças do sistema
│   │
│   └── modules/                        ← 📦 Docs por módulo (um arquivo por módulo)
│       ├── 01-auth-admin.md
│       ├── 02-auth-portal.md
│       ├── 03-contratos.md
│       ├── 04-instituicoes.md
│       ├── 05-produtos-pacotes.md
│       ├── 06-programacoes.md
│       ├── 07-condicoes-pagamento.md
│       ├── 08-descontos.md
│       ├── 09-termos-adesao.md
│       ├── 10-formandos.md
│       ├── 11-adesao-wizard.md
│       ├── 12-portal-area-formando.md
│       ├── 13-pagamentos-gateway.md
│       ├── 14-parcelas-financeiro.md
│       ├── 15-emails-notificacoes.md
│       ├── 16-relatorios.md
│       ├── 17-configuracoes-globais.md
│       ├── 18-auditoria-logs.md
│       ├── 19-acl-permissoes.md
│       └── 20-dashboard-admin.md
│
├── app/
│   ├── Actions/                        ← 🎯 Ações únicas e atômicas
│   │   ├── Adesao/
│   │   │   ├── CreateAdesaoFromWizardAction.php
│   │   │   ├── GenerateParcelasAction.php
│   │   │   └── ProcessAdesaoCheckoutAction.php
│   │   ├── Financeiro/
│   │   │   ├── BaixaManualParcelaAction.php
│   │   │   ├── ReemitirBoletoAction.php
│   │   │   └── CancelarParcelaAction.php
│   │   └── Contrato/
│   │       ├── DuplicarContratoAction.php
│   │       └── AplicarReajusteAction.php
│   │
│   ├── Console/
│   │   └── Commands/                   ← ⚡ Comandos Artisan customizados
│   │       ├── MarkOverdueParcelasCommand.php
│   │       ├── SendPaymentRemindersCommand.php
│   │       └── CleanExpiredDraftsCommand.php
│   │
│   ├── DTOs/                           ← 📋 Data Transfer Objects
│   │   ├── AdesaoCheckoutDTO.php
│   │   ├── ParcelamentoCalculoDTO.php
│   │   ├── PagamentoResultDTO.php
│   │   └── SimulacaoParcelasDTO.php
│   │
│   ├── Enums/                          ← 🏷️ Enums PHP 8.1+ (backed enums)
│   │   ├── ContratoStatus.php
│   │   ├── ModalidadePagamento.php
│   │   ├── StatusAdesao.php
│   │   ├── StatusParcela.php
│   │   ├── PapelPortalUserFormando.php
│   │   ├── TipoResponsavel.php
│   │   ├── OrigemAdesao.php
│   │   └── TipoConfiguracao.php
│   │
│   ├── Events/                         ← 📡 Eventos do domínio
│   │   ├── AdesaoConcluida.php
│   │   ├── PagamentoConfirmado.php
│   │   ├── ParcelaVencida.php
│   │   └── ReajusteAplicado.php
│   │
│   ├── Exceptions/                     ← ❌ Exceções customizadas
│   │   ├── AdesaoException.php
│   │   ├── PagamentoException.php
│   │   ├── ProgramacaoNaoEncontradaException.php
│   │   └── ParcelaMinimaException.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                  ← 🔧 Controllers do Backoffice
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── InstituicaoController.php
│   │   │   │   ├── ContratoController.php
│   │   │   │   ├── ProdutoController.php
│   │   │   │   ├── TermoController.php
│   │   │   │   ├── FormandoController.php
│   │   │   │   ├── ParcelaController.php
│   │   │   │   ├── RelatorioController.php
│   │   │   │   ├── SimuladorController.php
│   │   │   │   ├── ConfiguracaoController.php
│   │   │   │   ├── AdminUserController.php
│   │   │   │   ├── PerfilAclController.php
│   │   │   │   └── AuditLogController.php
│   │   │   │
│   │   │   ├── Portal/                 ← 🌐 Controllers do Portal do Formando
│   │   │   │   ├── HomeController.php
│   │   │   │   ├── AdesaoWizardController.php
│   │   │   │   ├── DashboardFormandoController.php
│   │   │   │   ├── ExtratoController.php
│   │   │   │   ├── ExtrasController.php
│   │   │   │   ├── DadosCadastraisController.php
│   │   │   │   └── SenhaController.php
│   │   │   │
│   │   │   └── Webhook/               ← 🔗 Webhooks de gateways
│   │   │       └── ItauWebhookController.php
│   │   │
│   │   ├── Middleware/
│   │   │   ├── AdminActive.php                 ← Verifica se admin está ativo
│   │   │   ├── CheckPermission.php             ← ACL granular por rota
│   │   │   ├── PortalFormandoContext.php        ← Injeta contexto do formando selecionado
│   │   │   ├── AdesaoContratoResolver.php       ← Valida e injeta contrato no wizard
│   │   │   ├── AdesaoStateGuard.php             ← Impede pulo de etapas no wizard
│   │   │   ├── AuditRequest.php                 ← Captura contexto para auditoria
│   │   │   └── VerifyWebhookSignature.php       ← Valida assinatura HMAC dos webhooks
│   │   │
│   │   └── Requests/                   ← ✅ Form Requests (validação)
│   │       ├── Admin/
│   │       │   ├── StoreInstituicaoRequest.php
│   │       │   ├── UpdateInstituicaoRequest.php
│   │       │   ├── StoreContratoRequest.php
│   │       │   ├── UpdateContratoRequest.php
│   │       │   ├── StoreProdutoRequest.php
│   │       │   ├── StoreProgramacaoRequest.php
│   │       │   ├── StoreCondicaoPagamentoRequest.php
│   │       │   ├── StoreDescontoRequest.php
│   │       │   ├── StoreTermoRequest.php
│   │       │   ├── BaixaManualRequest.php
│   │       │   └── StoreAdminUserRequest.php
│   │       └── Portal/
│   │           ├── AdesaoCadastroRequest.php
│   │           ├── AdesaoPagamentoRequest.php
│   │           ├── AdesaoCheckoutRequest.php
│   │           ├── CompraExtraRequest.php
│   │           └── UpdateDadosFormandoRequest.php
│   │
│   ├── Jobs/                           ← ⚙️ Jobs assíncronos (processados pelo Horizon)
│   │   ├── SendPaymentReminderJob.php
│   │   ├── MarkOverdueParcelasJob.php
│   │   ├── CleanExpiredDraftsJob.php
│   │   ├── ProcessWebhookPayloadJob.php
│   │   ├── GenerateBoletoJob.php
│   │   ├── SendEmailAdesaoJob.php
│   │   └── ApplyContratoReajusteJob.php
│   │
│   ├── Listeners/                      ← 👂 Listeners para Events
│   │   ├── SendAdesaoConfirmationEmail.php
│   │   ├── CreateAuditLogForPayment.php
│   │   └── NotifyAdminOnAdesao.php
│   │
│   ├── Livewire/                       ← ⚡ Componentes Livewire
│   │   ├── Admin/
│   │   │   ├── Contratos/
│   │   │   │   ├── ContratoTable.php
│   │   │   │   ├── ContratoForm.php
│   │   │   │   ├── CursoInlineEditor.php
│   │   │   │   └── PeriodoInlineEditor.php
│   │   │   ├── Produtos/
│   │   │   │   ├── ProdutoTable.php
│   │   │   │   ├── ProdutoForm.php
│   │   │   │   ├── ProgramacaoManager.php
│   │   │   │   ├── CondicaoPagamentoManager.php
│   │   │   │   ├── DescontoManager.php
│   │   │   │   └── TermoVinculacaoManager.php
│   │   │   ├── Formandos/
│   │   │   │   ├── FormandoTable.php
│   │   │   │   ├── FormandoFicha.php
│   │   │   │   ├── ExtratoFinanceiro.php
│   │   │   │   └── CadastroManualWizard.php
│   │   │   ├── Financeiro/
│   │   │   │   ├── ParcelaConsolidadaTable.php
│   │   │   │   ├── SimuladorParcelamento.php
│   │   │   │   └── RelatorioGenerator.php
│   │   │   ├── Configuracoes/
│   │   │   │   └── ConfiguracoesGlobaisForm.php
│   │   │   └── Dashboard/
│   │   │       ├── KpiCards.php
│   │   │       ├── AdesoesMensaisChart.php
│   │   │       └── AlertasSistema.php
│   │   │
│   │   └── Portal/
│   │       ├── Adesao/
│   │       │   ├── WizardShell.php
│   │       │   ├── StepCodigoTurma.php
│   │       │   ├── StepCursoPeriodo.php
│   │       │   ├── StepProdutos.php
│   │       │   ├── StepCadastro.php
│   │       │   ├── StepPagamento.php
│   │       │   ├── StepConferencia.php
│   │       │   └── StepCheckout.php
│   │       ├── Area/
│   │       │   ├── DashboardFormando.php
│   │       │   ├── ExtratoFinanceiro.php
│   │       │   ├── CatalogoExtras.php
│   │       │   ├── DadosCadastrais.php
│   │       │   └── SelectorMultiFormando.php
│   │       └── Auth/
│   │           ├── LoginPortal.php
│   │           └── RecuperarSenha.php
│   │
│   ├── Mail/                           ← 📧 Mailables
│   │   ├── AdesaoConcluidaMail.php
│   │   ├── BoletoGeradoMail.php
│   │   ├── PagamentoConfirmadoMail.php
│   │   ├── LembreteVencimentoMail.php
│   │   ├── ParcelaVencidaMail.php
│   │   ├── BoletoReemitidoMail.php
│   │   └── RecuperacaoSenhaMail.php
│   │
│   ├── Models/                         ← 🗃️ Eloquent Models
│   │   ├── AdminUser.php
│   │   ├── AdminPerfil.php
│   │   ├── AdminPermissao.php
│   │   ├── PortalUser.php
│   │   ├── Instituicao.php
│   │   ├── Contrato.php
│   │   ├── ContratoCurso.php
│   │   ├── ContratoPeriodo.php
│   │   ├── IndiceReajuste.php
│   │   ├── ContratoReajuste.php
│   │   ├── CategoriaProduto.php
│   │   ├── ContratoProduto.php
│   │   ├── ProdutoProgramacao.php
│   │   ├── ProdutoCondicaoPagamento.php
│   │   ├── ProdutoDesconto.php
│   │   ├── Termo.php
│   │   ├── ProdutoTermo.php
│   │   ├── Formando.php
│   │   ├── PortalUserFormando.php
│   │   ├── Responsavel.php
│   │   ├── Adesao.php
│   │   ├── AdesaoProduto.php
│   │   ├── AceiteTermo.php
│   │   ├── Parcela.php
│   │   ├── Pagamento.php
│   │   ├── PagamentoEvento.php
│   │   ├── AdesaoDraft.php
│   │   ├── AuditLog.php
│   │   ├── EmailLog.php
│   │   └── ConfiguracaoGlobal.php
│   │
│   ├── Notifications/                  ← 🔔 Notifications (admin in-app)
│   │   ├── NovaAdesaoNotification.php
│   │   ├── PagamentoRecebidoNotification.php
│   │   └── AlertaInadimplenciaNotification.php
│   │
│   ├── Observers/                      ← 👁️ Model Observers (auditoria automática)
│   │   ├── ParcelaObserver.php
│   │   ├── AdesaoObserver.php
│   │   ├── FormandoObserver.php
│   │   └── ConfiguracaoGlobalObserver.php
│   │
│   ├── Policies/                       ← 🛡️ Authorization Policies
│   │   ├── ContratoPolicy.php
│   │   ├── ProdutoPolicy.php
│   │   ├── FormandoPolicy.php
│   │   ├── ParcelaPolicy.php
│   │   └── ConfiguracaoPolicy.php
│   │
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   └── GatewayServiceProvider.php  ← Registra o driver de gateway ativo
│   │
│   ├── Services/                       ← 🧠 Services (CORAÇÃO DA LÓGICA)
│   │   ├── Adesao/
│   │   │   ├── AdesaoDraftService.php
│   │   │   ├── AdesaoCheckoutService.php
│   │   │   ├── AdesaoFinalizeService.php
│   │   │   └── AdesaoSnapshotService.php
│   │   ├── Contrato/
│   │   │   ├── ContratoResolverService.php
│   │   │   └── ContratoReajusteService.php
│   │   ├── Financeiro/
│   │   │   ├── ParcelamentoCalculatorService.php
│   │   │   ├── PrimeiroVencimentoService.php
│   │   │   ├── CalendarioParcelasService.php
│   │   │   ├── ParcelaValorMinimoService.php
│   │   │   ├── ExtratoFinanceiroService.php
│   │   │   ├── ParcelaStatusUpdaterService.php
│   │   │   └── CobrancaReemissaoService.php
│   │   ├── Gateway/                    ← 🏦 Driver Pattern para gateways
│   │   │   ├── Contracts/
│   │   │   │   └── GatewayInterface.php
│   │   │   ├── GatewayManager.php      ← Factory/Manager (resolve o driver ativo)
│   │   │   ├── Drivers/
│   │   │   │   ├── ItauGatewayService.php
│   │   │   │   ├── ItauBoletoService.php
│   │   │   │   ├── ItauPixService.php
│   │   │   │   ├── ItauCartaoService.php
│   │   │   │   └── MockGatewayService.php
│   │   │   └── WebhookPagamentoService.php
│   │   ├── Pagamento/
│   │   │   ├── PagamentoRetryService.php
│   │   │   ├── DescontoAplicavelService.php
│   │   │   ├── CondicaoPagamentoDisponivelService.php
│   │   │   └── ModalidadeHibridaResolverService.php
│   │   ├── Portal/
│   │   │   ├── PortalUserResolverService.php
│   │   │   ├── PortalUserVinculacaoService.php
│   │   │   └── PortalAuthFlowService.php
│   │   ├── Produto/
│   │   │   ├── ProdutoDisponibilidadeService.php
│   │   │   ├── ProgramacaoAtivaService.php
│   │   │   └── ProdutoGrupoExclusivoService.php
│   │   ├── Termo/
│   │   │   ├── TermoInterpolatorService.php
│   │   │   ├── TermoConsolidatorService.php
│   │   │   ├── TermoPdfService.php
│   │   │   └── AceiteTermoRecorderService.php
│   │   └── Auditoria/
│   │       └── AuditLogService.php
│   │
│   ├── Support/                        ← 🔧 Helpers e utilitários
│   │   ├── Helpers/
│   │   │   ├── MoneyHelper.php         ← Formatação monetária BR
│   │   │   ├── CpfCnpjHelper.php       ← Validação e máscara
│   │   │   └── DateHelper.php          ← Datas com fuso BR
│   │   └── Traits/
│   │       ├── HasAuditLog.php         ← Trait para models auditáveis
│   │       ├── HasSnapshotData.php     ← Trait para dados snapshotados
│   │       ├── Filterable.php          ← Trait para queries com filtros
│   │       └── HasMoney.php            ← Trait para campos monetários (centavos)
│   │
│   └── View/
│       └── Components/                 ← 🎨 Blade Components (anonymous + class-based)
│           ├── Admin/                  ← Components exclusivos do admin
│           │   ├── Layout.php
│           │   ├── Sidebar.php
│           │   ├── Header.php
│           │   ├── Breadcrumb.php
│           │   ├── DataTable.php
│           │   ├── KpiCard.php
│           │   ├── StatusBadge.php
│           │   ├── FilterPanel.php
│           │   ├── ConfirmModal.php
│           │   ├── DrawerForm.php
│           │   └── ActionDropdown.php
│           ├── Portal/                 ← Components exclusivos do portal
│           │   ├── Layout.php
│           │   ├── Header.php
│           │   ├── Footer.php
│           │   ├── WizardProgress.php
│           │   ├── PackageCard.php
│           │   ├── ParcelaCard.php
│           │   └── FormandoSelector.php
│           └── Shared/                 ← Components compartilhados
│               ├── Alert.php
│               ├── Button.php
│               ├── Input.php
│               ├── Select.php
│               ├── Toggle.php
│               ├── FileUpload.php
│               ├── MoneyInput.php
│               ├── CepInput.php
│               ├── CpfInput.php
│               ├── DatePicker.php
│               ├── Toast.php
│               ├── LoadingButton.php
│               └── EmptyState.php
│
├── config/
│   ├── gateway.php                     ← Config do gateway de pagamentos
│   ├── formatura.php                   ← Config geral do sistema de formaturas
│   └── audit.php                       ← Config de auditoria
│
├── database/
│   ├── factories/                      ← Factories para testes e seeders
│   ├── migrations/
│   │   ├── 2026_04_01_000001_create_admin_perfis_table.php
│   │   ├── 2026_04_01_000002_create_admin_permissoes_table.php
│   │   ├── ...                         ← (ver Sprint 2 e 3 do PRD)
│   │   └── 2026_04_01_000031_create_pagamento_eventos_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── DevelopmentSeeder.php        ← Seeder completo para dev (essencial!)
│       ├── AdminPerfilPermissaoSeeder.php
│       ├── ConfiguracaoGlobalSeeder.php
│       ├── InstituicaoSeeder.php
│       ├── ContratoCompletoSeeder.php   ← Contrato + cursos + períodos + produtos
│       └── FormandoTesteSeeder.php
│
├── resources/
│   ├── css/
│   │   ├── admin.css                   ← Estilos customizados do admin
│   │   ├── portal.css                  ← Estilos customizados do portal
│   │   └── app.css                     ← Tailwind base imports
│   │
│   ├── js/
│   │   ├── admin.js                    ← Entry point JS do admin
│   │   ├── portal.js                   ← Entry point JS do portal
│   │   └── plugins/                    ← Plugins JS organizados
│   │       ├── apex-charts-init.js
│   │       ├── flatpickr-init.js
│   │       ├── choices-init.js
│   │       ├── inputmask-init.js
│   │       └── tinymce-init.js
│   │
│   └── views/
│       ├── admin/                      ← 🔧 Views do Backoffice
│       │   ├── layouts/
│       │   │   ├── app.blade.php       ← Layout master do admin (Inspinia)
│       │   │   └── auth.blade.php      ← Layout de auth do admin
│       │   ├── auth/
│       │   │   ├── login.blade.php
│       │   │   └── forgot-password.blade.php
│       │   ├── dashboard/
│       │   │   └── index.blade.php
│       │   ├── instituicoes/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   └── edit.blade.php
│       │   ├── contratos/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   ├── edit.blade.php
│       │   │   └── show.blade.php
│       │   ├── produtos/
│       │   ├── termos/
│       │   ├── formandos/
│       │   ├── financeiro/
│       │   ├── relatorios/
│       │   ├── configuracoes/
│       │   ├── usuarios/
│       │   ├── perfis/
│       │   └── audit-logs/
│       │
│       ├── portal/                     ← 🌐 Views do Portal
│       │   ├── layouts/
│       │   │   ├── app.blade.php       ← Layout master do portal
│       │   │   ├── auth.blade.php      ← Layout de auth do portal
│       │   │   └── guest.blade.php     ← Layout público (adesão)
│       │   ├── home.blade.php
│       │   ├── adesao/
│       │   │   ├── wizard.blade.php    ← Shell do wizard
│       │   │   ├── steps/
│       │   │   │   ├── codigo.blade.php
│       │   │   │   ├── curso-periodo.blade.php
│       │   │   │   ├── produtos.blade.php
│       │   │   │   ├── cadastro.blade.php
│       │   │   │   ├── pagamento.blade.php
│       │   │   │   ├── conferencia.blade.php
│       │   │   │   └── checkout.blade.php
│       │   │   ├── sucesso.blade.php
│       │   │   └── falha.blade.php
│       │   ├── auth/
│       │   │   ├── login.blade.php
│       │   │   └── forgot-password.blade.php
│       │   ├── area/
│       │   │   ├── dashboard.blade.php
│       │   │   ├── extrato.blade.php
│       │   │   ├── extras.blade.php
│       │   │   ├── dados.blade.php
│       │   │   └── senha.blade.php
│       │   └── partials/
│       │       └── formando-selector.blade.php
│       │
│       ├── components/                 ← 🎨 Blade Components (views)
│       │   ├── admin/
│       │   │   ├── layout.blade.php
│       │   │   ├── sidebar.blade.php
│       │   │   ├── header.blade.php
│       │   │   ├── breadcrumb.blade.php
│       │   │   ├── data-table.blade.php
│       │   │   ├── kpi-card.blade.php
│       │   │   ├── status-badge.blade.php
│       │   │   ├── filter-panel.blade.php
│       │   │   ├── confirm-modal.blade.php
│       │   │   ├── drawer-form.blade.php
│       │   │   └── action-dropdown.blade.php
│       │   ├── portal/
│       │   │   ├── layout.blade.php
│       │   │   ├── header.blade.php
│       │   │   ├── footer.blade.php
│       │   │   ├── wizard-progress.blade.php
│       │   │   ├── package-card.blade.php
│       │   │   ├── parcela-card.blade.php
│       │   │   └── formando-selector.blade.php
│       │   └── shared/
│       │       ├── alert.blade.php
│       │       ├── button.blade.php
│       │       ├── input.blade.php
│       │       ├── select.blade.php
│       │       ├── toggle.blade.php
│       │       ├── file-upload.blade.php
│       │       ├── money-input.blade.php
│       │       ├── cep-input.blade.php
│       │       ├── cpf-input.blade.php
│       │       ├── date-picker.blade.php
│       │       ├── toast.blade.php
│       │       ├── loading-button.blade.php
│       │       └── empty-state.blade.php
│       │
│       ├── emails/                     ← 📧 Templates de e-mail
│       │   ├── adesao-concluida.blade.php
│       │   ├── boleto-gerado.blade.php
│       │   ├── pagamento-confirmado.blade.php
│       │   ├── lembrete-vencimento.blade.php
│       │   ├── parcela-vencida.blade.php
│       │   ├── boleto-reemitido.blade.php
│       │   └── recuperacao-senha.blade.php
│       │
│       └── pdf/                        ← 📄 Templates de PDF
│           ├── termos-consolidados.blade.php
│           └── boleto.blade.php
│
├── routes/
│   ├── web.php                         ← Rota raiz (redirect ou landing)
│   ├── admin.php                       ← TODAS as rotas do admin (prefixo /admin)
│   ├── portal.php                      ← TODAS as rotas do portal
│   └── webhook.php                     ← Rotas de webhooks (sem CSRF)
│
├── tests/
│   ├── Feature/
│   │   ├── Admin/
│   │   │   ├── AuthAdminTest.php
│   │   │   ├── ContratoTest.php
│   │   │   └── ...
│   │   ├── Portal/
│   │   │   ├── AdesaoWizardTest.php
│   │   │   ├── ExtratoTest.php
│   │   │   └── ...
│   │   └── Webhook/
│   │       └── ItauWebhookTest.php
│   └── Unit/
│       ├── Services/
│       │   ├── ParcelamentoCalculatorTest.php   ← 15+ cenários
│       │   ├── ProgramacaoAtivaTest.php
│       │   └── DescontoAplicavelTest.php
│       └── Models/
│           └── ...
│
├── laradock/                           ← Infra Docker (ver INFRA.md)
├── Makefile                            ← Comandos do dia a dia
├── .editorconfig                       ← Padrões de formatação
├── .php-cs-fixer.dist.php              ← Configuração PHP CS Fixer
├── phpstan.neon                        ← Configuração PHPStan
├── vite.config.js                      ← Configuração Vite (2 entry points)
└── tailwind.config.js                  ← Tailwind config (Inspinia + custom)
```

---

## 3. Quando Usar Cada Padrão Laravel

Esta seção define claramente QUANDO usar cada padrão arquitetural. Segue a regra: **se está em dúvida, é um Service**.

### 3.1 Controller

**Quando:** Receber a request, validar via FormRequest, chamar Service/Action, retornar response.  
**Regra:** Controller NUNCA contém lógica de negócio. Máximo 5-7 linhas por método.

```php
// ✅ BOM — Controller magro
public function store(StoreContratoRequest $request)
{
    $contrato = app(CreateContratoAction::class)->execute($request->validated());
    return redirect()->route('admin.contratos.show', $contrato)
        ->with('success', 'Contrato criado com sucesso.');
}

// ❌ RUIM — Controller gordo
public function store(Request $request)
{
    $request->validate([...]); // validação no controller
    $contrato = new Contrato();
    $contrato->fill($request->all()); // lógica no controller
    // ... 30 linhas de regra de negócio
}
```

### 3.2 Service

**Quando:** Lógica de negócio reutilizável, que pode ser chamada por Controllers, Jobs, Commands ou outros Services.  
**Regra:** Services são stateless. Recebem dados, processam, retornam resultado. São o **coração** do sistema.

```php
// Exemplos clássicos de Services neste projeto:
ParcelamentoCalculatorService     → cálculo dinâmico de parcelas
ProgramacaoAtivaService           → busca programação vigente por data
DescontoAplicavelService          → resolve desconto por modalidade/faixa
AdesaoCheckoutService             → orquestra todo o checkout da adesão
ExtratoFinanceiroService          → monta extrato com totalizadores
```

### 3.3 Action

**Quando:** Operação atômica e complexa que representa uma intenção específica do sistema. Diferente de Service porque é uma ação pontual com um único método `execute()`.  
**Regra:** Uma Action faz UMA coisa. Se precisa fazer duas, use dois Actions ou um Service que orquestra.

```php
// Exemplos de Actions neste projeto:
CreateAdesaoFromWizardAction     → cria adesão a partir dos dados do wizard
GenerateParcelasAction           → gera todas as parcelas de uma adesão
BaixaManualParcelaAction         → registra baixa manual com auditoria
DuplicarContratoAction           → duplica contrato com todos os vínculos
```

### 3.4 Job

**Quando:** Processamento assíncrono que NÃO precisa bloquear a request do usuário.  
**Regra:** Jobs são sempre dispatched para filas (Horizon). Devem ser idempotentes (executar 2x não causa problema).

```php
// Quando usar Job vs execução síncrona:
// JOB: enviar e-mail, gerar PDF, chamar API externa, processamento pesado
// SÍNCRONO: salvar no banco, calcular valor, validar dados
```

### 3.5 Event + Listener

**Quando:** Algo aconteceu e OUTROS módulos podem querer reagir. Desacopla o "o que aconteceu" do "o que fazer sobre isso".  
**Regra:** O emissor do evento NÃO deve depender dos listeners.

```php
// Evento: AdesaoConcluida
// Listeners possíveis:
//   - SendAdesaoConfirmationEmail
//   - CreateAuditLogForAdesao
//   - NotifyAdminOnAdesao
//   - UpdateDashboardMetrics
```

### 3.6 Observer

**Quando:** Auditoria automática e side-effects vinculados ao ciclo de vida do Model (creating, updating, deleting).  
**Regra:** NÃO colocar lógica de negócio em Observer. Usar apenas para auditoria e efeitos colaterais simples.

### 3.7 Middleware

**Quando:** Lógica que deve rodar ANTES ou DEPOIS de toda request de um grupo de rotas.  
**Exemplos:** Verificar se admin está ativo, injetar contexto do formando, validar webhook.

### 3.8 FormRequest

**Quando:** SEMPRE que um Controller receber dados do usuário. Sem exceção.  
**Regra:** Toda validação de input fica no FormRequest, nunca no Controller.

### 3.9 Policy

**Quando:** Autorização baseada no usuário e na entidade. "Este admin PODE editar ESTE contrato?"  
**Regra:** Policies complementam o Middleware de ACL. O Middleware verifica permissão genérica, a Policy verifica permissão contextual.

### 3.10 DTO (Data Transfer Object)

**Quando:** Precisa transportar dados estruturados entre camadas sem usar arrays.  
**Regra:** DTOs são imutáveis (readonly). Sem lógica, apenas dados. Sempre incluir `toArray()` para serialização (API-Ready).

```php
readonly class ParcelamentoCalculoDTO
{
    public function __construct(
        public int $totalParcelas,
        public int $valorTotalCentavos,
        public int $valorParcelaCentavos,
        public int $descontoPercentual,
        public Carbon $primeiroVencimento,
        public Carbon $ultimoVencimento,
        public array $cronograma,
    ) {}

    public function toArray(): array
    {
        return [
            'total_parcelas' => $this->totalParcelas,
            'valor_total' => MoneyHelper::format($this->valorTotalCentavos),
            'valor_total_centavos' => $this->valorTotalCentavos,
            'valor_parcela' => MoneyHelper::format($this->valorParcelaCentavos),
            'valor_parcela_centavos' => $this->valorParcelaCentavos,
            'desconto_percentual' => $this->descontoPercentual,
            'primeiro_vencimento' => $this->primeiroVencimento->format('Y-m-d'),
            'ultimo_vencimento' => $this->ultimoVencimento->format('Y-m-d'),
            'cronograma' => $this->cronograma,
        ];
    }
}
```

### 3.11 Enum

**Quando:** SEMPRE que um campo tem valores finitos e conhecidos.  
**Regra:** Usar PHP 8.1+ Backed Enums com value string para persistência no banco.

```php
enum StatusParcela: string
{
    case PENDENTE = 'pendente';
    case PAGO = 'pago';
    case VENCIDO = 'vencido';
    case CANCELADO = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::PAGO => 'Pago',
            self::VENCIDO => 'Vencido',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDENTE => 'yellow',
            self::PAGO => 'green',
            self::VENCIDO => 'red',
            self::CANCELADO => 'gray',
        };
    }
}
```

### 3.12 Trait

**Quando:** Comportamento compartilhado entre Models ou Classes que NÃO compartilham herança.  
**Regra:** Traits NÃO devem conter lógica de negócio. Apenas comportamentos reutilizáveis.

### 3.13 Resumo Visual de Decisão

```
Preciso validar input do usuário?
  → FormRequest

Preciso transformar request em resposta?
  → Controller (chama Service/Action)

Preciso de lógica de negócio reutilizável?
  → Service

Preciso de uma operação atômica e complexa?
  → Action

Preciso processar algo sem bloquear o usuário?
  → Job (via Horizon)

Algo aconteceu e outros módulos devem saber?
  → Event + Listener

Preciso de auditoria automática no Model?
  → Observer

Preciso de lógica antes/depois de TODA request?
  → Middleware

Preciso verificar se o usuário pode fazer X com Y?
  → Policy

Preciso transportar dados entre camadas?
  → DTO

Preciso de valores finitos para um campo?
  → Enum

Preciso de comportamento reutilizável entre classes?
  → Trait
```

---

## 4. Separação Admin vs Portal

### 4.1 Guards de Autenticação

```php
// config/auth.php
'guards' => [
    'admin' => [
        'driver' => 'session',
        'provider' => 'admin_users',
    ],
    'portal' => [
        'driver' => 'session',
        'provider' => 'portal_users',
    ],
],
'providers' => [
    'admin_users' => [
        'driver' => 'eloquent',
        'model' => App\Models\AdminUser::class,
    ],
    'portal_users' => [
        'driver' => 'eloquent',
        'model' => App\Models\PortalUser::class,
    ],
],
```

### 4.2 Rotas Separadas

```php
// routes/admin.php — Prefixo /admin, guard admin
Route::prefix('admin')->name('admin.')->group(function () {
    // Auth
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login']);
    });

    // Autenticado
    Route::middleware(['auth:admin', 'admin.active'])->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('instituicoes', InstituicaoController::class);
        Route::resource('contratos', ContratoController::class);
        // ...
    });
});

// routes/portal.php — Prefixo /portal, guard portal
Route::prefix('portal')->name('portal.')->group(function () {
    // Público (adesão)
    Route::get('/', HomeController::class)->name('home');
    Route::get('adesao/{codigo_turma}', AdesaoWizardController::class)->name('adesao');

    // Autenticado
    Route::middleware('auth:portal')->group(function () {
        Route::get('dashboard', DashboardFormandoController::class)->name('dashboard');
        // ...
    });
});
```

### 4.3 Vite com Dois Entry Points

```js
// vite.config.js
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/admin.css',
                'resources/js/admin.js',
                'resources/css/portal.css',
                'resources/js/portal.js',
            ],
            refresh: true,
        }),
    ],
});
```

---

## 5. Arquitetura API-Ready

> **Princípio:** A lógica de negócio é escrita UMA VEZ nos Services e serve para qualquer porta de entrada — Livewire (hoje), API REST (app mobile amanhã), Commands, Jobs.

### 5.1 O Que É

API-Ready não é um framework, não é uma biblioteca. É uma disciplina de organização de código onde:

- **Services nunca recebem `Request`** como parâmetro → recebem dados tipados (Models, Enums, int, string, Carbon)
- **Services nunca retornam `redirect()`, `view()` ou `response()->json()`** → retornam DTOs ou Models
- **DTOs sempre têm `toArray()`** → prontos para serialização JSON quando a API for necessária
- **Quem decide o formato de resposta é o Controller/Livewire**, nunca o Service

### 5.2 Como Funciona na Prática

```php
// SERVICE — lógica pura, desacoplada de HTTP
class ParcelamentoCalculatorService
{
    public function calcular(
        ContratoProduto $produto,          // Model, não request->produto_id
        ModalidadePagamento $modalidade,   // Enum, não request->modalidade
        int $parcelas,                     // Primitivo tipado
        ?int $diaVencimento = null,
    ): ParcelamentoCalculoDTO {            // Retorna DTO, não response
        // ... toda a lógica aqui
        return new ParcelamentoCalculoDTO(...);
    }
}
```

```php
// HOJE — Livewire consome o Service
class StepPagamento extends Component
{
    public function calcularParcelas(): void
    {
        $this->resultado = app(ParcelamentoCalculatorService::class)->calcular(
            produto: $this->produto,
            modalidade: $this->modalidade,
            parcelas: $this->qtdParcelas,
        );
    }
}
```

```php
// AMANHÃ — API Controller consome o MESMO Service (zero reescrita)
class ParcelamentoApiController extends Controller
{
    public function calcular(
        CalcParcelamentoRequest $request,
        ParcelamentoCalculatorService $service,
    ): JsonResponse {
        $resultado = $service->calcular(
            produto: ContratoProduto::findOrFail($request->produto_id),
            modalidade: ModalidadePagamento::from($request->modalidade),
            parcelas: $request->parcelas,
        );

        return response()->json($resultado->toArray());
    }
}
```

### 5.3 Regras API-Ready (obrigatórias)

| Regra                    | ❌ Errado                       | ✅ Certo                                            |
| ------------------------ | ------------------------------- | --------------------------------------------------- |
| Service recebe Request   | `calcular(Request $request)`    | `calcular(ContratoProduto $produto, int $parcelas)` |
| Service retorna HTTP     | `return redirect()->route(...)` | `return new AdesaoResultDTO(...)`                   |
| Service acessa session   | `session()->get('draft')`       | Receber draft como parâmetro                        |
| Service formata resposta | `return response()->json(...)`  | `return $dto` (Controller formata)                  |
| DTO sem toArray          | Classe sem serialização         | `toArray()` em todo DTO                             |

### 5.4 Estrutura de API Futura (quando o app mobile chegar)

```
routes/
├── admin.php              ← Rotas do admin (Livewire/Blade)
├── portal.php             ← Rotas do portal web (Livewire/Blade)
├── api.php                ← 🆕 Rotas da API (Sanctum tokens) — FUTURO
└── webhook.php            ← Webhooks (sem CSRF)

app/Http/Controllers/
├── Admin/                 ← Controllers admin (chamam Services)
├── Portal/                ← Controllers portal (chamam Services)
├── Api/                   ← 🆕 Controllers API (chamam os MESMOS Services) — FUTURO
│   ├── AuthApiController.php
│   ├── AdesaoApiController.php
│   ├── ExtratoApiController.php
│   ├── ExtrasApiController.php
│   └── FormandoApiController.php
└── Webhook/               ← Webhooks

app/Http/Resources/        ← 🆕 API Resources (formatação JSON) — FUTURO
├── FormandoResource.php
├── ParcelaResource.php
├── ProdutoResource.php
└── AdesaoResource.php
```

### 5.5 Reaproveitamento Estimado para App Mobile

| Camada                             |         Reaproveitamento          |
| ---------------------------------- | :-------------------------------: |
| Models, Enums, Traits              |             **100%**              |
| Services, Actions                  |             **100%**              |
| DTOs                               |             **100%**              |
| Jobs, Events, Listeners, Observers |             **100%**              |
| Exceptions                         |             **100%**              |
| Middleware                         |  ~80% (adaptar para token auth)   |
| FormRequests                       | ~70% (mesmas rules, novo request) |
| Controllers de API                 |    Criar novos (~2-3 sprints)     |
| App Mobile (React Native)          |     Criar novo (~6-8 sprints)     |

**Economia estimada vs refatoração:** 4-6 sprints (~1-1.5 meses)

---

## 6. Documentação Modular — Como Funciona

Cada módulo tem seu próprio arquivo de documentação em `.docs/modules/`. A estrutura padrão de cada arquivo é:

```markdown
# Módulo: [Nome do Módulo]

**Sprint:** [Sprint onde foi criado]  
**Última Atualização:** [data]  
**Status:** 🟢 Completo | 🟡 Em Progresso | 🔴 Pendente

## Escopo

Descrição do que este módulo faz.

## Models Envolvidos

Lista de Models com campos principais.

## Services e Actions

Lista de Services/Actions com assinatura e responsabilidade.

## Rotas

Tabela de rotas com método, URI, Controller e Middleware.

## Components Blade

Lista de componentes criados para este módulo.

## Regras de Negócio

Lista numerada de regras com referência ao PRD.

## Telas / UI

Referência visual (screenshots ou links para componentes Inspinia usados).

## Testes

Lista de cenários de teste.

## Dependências

Módulos dos quais este depende.

## Changelog do Módulo

| Data | Descrição |
| ---- | --------- |
```

**Regra de ouro:** Quando terminar de desenvolver um módulo, ATUALIZE o respectivo arquivo em `.docs/modules/` antes de fazer o commit.

---

## 7. Memória e Contexto para IA

Para manter o contexto correto ao usar Claude Code ou Cursor AI, siga esta estratégia:

### 7.1 Arquivo CLAUDE.md (na raiz do projeto)

O CLAUDE.md definitivo está em `/mnt/user-data/outputs/CLAUDE.md` e cobre 20 seções incluindo API-Ready. Consultar diretamente o arquivo.

### 7.2 Memória no Claude (via memory_user_edits)

Manter no Claude as informações mais relevantes do projeto para contextualizar conversas futuras.

---

## 8. Versionamento e Commits

Ver documento detalhado: `.docs/CONVENTIONS.md`

Resumo rápido do padrão de commits:

```
tipo(escopo): descrição curta

Tipos: feat, fix, refactor, docs, style, test, chore, perf
Escopos: admin, portal, gateway, financeiro, adesao, auth, infra
```

Exemplos:

```
feat(portal): implementar etapa 3 do wizard (seleção de pacotes)
fix(financeiro): corrigir cálculo de desconto para modalidade híbrida
docs(modules): atualizar documentação do módulo de contratos
refactor(gateway): extrair lógica de retry para PagamentoRetryService
test(parcelas): adicionar cenários de cálculo dinâmico
chore(infra): atualizar Laradock para PHP 8.4
```
