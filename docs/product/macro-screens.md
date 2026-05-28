---
title: Telas Macro — Portal ArtFinal v2
version: 1.0.0
date: 2026-04-18
status: draft
---

# Telas Macro — Portal ArtFinal v2

> Catálogo de telas dos três canais (Admin, Portal SPA, Mobile RN) com rota, persona, propósito, componentes, ações, dados exibidos e estados (loading/empty/error/success).
> Fontes: [`PRD_v4.md`](../prd/PRD_v4.md) §telas, [`PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) §2 rotas API, [`PRD_EXPANDED.md`](./PRD_EXPANDED.md).
> Documentos irmãos: [Brief](./PROJECT_BRIEF.md) · [User flows](./user-flows.md) · [Jornadas](./journeys-personas.md) · [SRS](./SRS.md).

---

## Sumário

- [Convenções](#convenções)
- [1. Admin (Blade + Livewire + Inspinia)](#1-admin-blade--livewire--inspinia)
- [2. Portal SPA (React)](#2-portal-spa-react)
- [3. Mobile RN (F8 futuro)](#3-mobile-rn-f8-futuro)
- [4. Catálogo de estados](#4-catálogo-de-estados)

---

## Convenções

- Todas as rotas da API referenciadas em [`user-flows.md`](./user-flows.md) e [`../prd/PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) §2.
- **Estados UI** obrigatórios em TODA tela com dados: `loading`, `empty`, `error`, `success`.
- Nomenclatura de componentes:
    - Admin: `<x-admin.*>` (Blade + Inspinia).
    - Portal SPA: componentes React (PascalCase em `resources/spa/src/pages/...`).
    - Mobile: componentes RN `<ScreenName>`.
- Todas as telas Admin respeitam catálogo de componentes Inspinia — ver CLAUDE.md §11.
- Todas as telas Portal usam Tailwind + componentes compartilhados a definir em F3.

---

## 1. Admin (Blade + Livewire + Inspinia)

### 1.1 Login Admin

| Propriedade   | Valor                                        |
| ------------- | -------------------------------------------- |
| **Rota**      | `GET /admin/login`, `POST /admin/login`      |
| **Persona**   | Admin, Comissão, Operação                    |
| **Propósito** | Autenticar usuário administrativo via sessão |
| **Guard**     | `auth:admin` (sessão)                        |

**Componentes principais.** `<x-admin.auth.login-card>`, `<x-shared.alert-error>`, CSRF token, input email/senha com validação client-side mínima.

**Ações.** Submit login, link "Esqueci minha senha", toggle de visibilidade de senha.

**Dados exibidos.** Nenhum (pré-autenticação).

**Estados.**

- `loading`: botão desabilitado, spinner no CTA.
- `empty`: form em branco.
- `error`: banner vermelho com `error.message` do envelope §2.11; rate limit → contador regressivo.
- `success`: redirect para `/admin/dashboard`.

---

### 1.2 Dashboard Admin

| Propriedade   | Valor                                                |
| ------------- | ---------------------------------------------------- |
| **Rota**      | `GET /admin/dashboard`                               |
| **Persona**   | Admin, Comissão (subset)                             |
| **Propósito** | Visão consolidada de KPIs operacionais e financeiros |

**Componentes principais.** `<x-admin.kpi-card>` × 6, gráfico ApexCharts (adesões por dia), tabela de eventos ativos, widget de alertas recentes.

**Ações.** Trocar período (7d/30d/90d), filtrar por organização/evento, drill-down em cada KPI.

**Dados exibidos.** Total formandos ativos, % adesão vs meta, MRR, inadimplência, taxa RSVP confirmado, reservas confirmadas por evento, fila de aprovações pendentes.

**Estados.**

- `loading`: skeleton nos cards e gráfico.
- `empty`: "Nenhum evento ativo" + CTA criar evento.
- `error`: banner global + KPIs individuais com fallback `--`.
- `success`: valores preenchidos + último update timestamp.

---

### 1.3 Listagem de Eventos

| Propriedade   | Valor                                          |
| ------------- | ---------------------------------------------- |
| **Rota**      | `GET /admin/eventos`                           |
| **Persona**   | Admin, Comissão (filtrado por escopo)          |
| **Propósito** | CRUD de eventos (rascunho/publicado/encerrado) |

**Componentes principais.** `<x-admin.data-table :selectable :exportable>` com colunas (ULID público mascarado, nome, data, status, turmas, adesões), `<x-admin.filter-bar>`, `<x-admin.modal>` para exclusão.

**Ações.** Criar, editar, publicar, cancelar, duplicar, exportar CSV.

**Dados exibidos.** `eventos.nome`, `data_evento`, `status` (badge colorida via Enum), `qtd_turmas`, `qtd_formandos`, `qtd_adesoes_ativas`.

**Estados.**

- `loading`: `<x-admin.skeleton-table :rows=10>`.
- `empty`: ilustração + CTA "Criar primeiro evento".
- `error`: banner + botão retry.
- `success`: tabela + paginação length-aware (tabela pequena).

---

### 1.4 Detalhe do Evento

| Propriedade   | Valor                                                      |
| ------------- | ---------------------------------------------------------- |
| **Rota**      | `GET /admin/eventos/{id}`                                  |
| **Persona**   | Admin, Comissão                                            |
| **Propósito** | Hub operacional do evento (cadastro, janelas, KPIs, ações) |

**Componentes principais.** `<x-admin.tabs>` (Visão geral, Janelas, Pacotes, Cotas, Mapa, Relatórios), `<x-admin.info-block>`, `<x-admin.timeline>` de eventos auditados.

**Ações.** Editar janelas, publicar, duplicar, cancelar, baixar relatório, configurar cotas.

**Dados exibidos.** Todas as colunas de `eventos`, pivô `turmas_evento`, `mapas_mesas`, contadores.

**Estados.** Idem padrão.

---

### 1.5 Gestão de Pacotes e Produtos

| Propriedade   | Valor                                                           |
| ------------- | --------------------------------------------------------------- |
| **Rota**      | `GET /admin/comercial/pacotes`, `GET /admin/comercial/produtos` |
| **Persona**   | Admin                                                           |
| **Propósito** | Modelar oferta comercial: preço, parcelas, produtos inclusos    |

**Componentes.** DataTable, modal de edição, `<x-admin.money-input>` (centavos), `<x-admin.products-picker>` (Choices.js wrapper).

**Ações.** CRUD, duplicar pacote, inativar (sem deletar).

**Dados.** `preco_centavos`, `qtd_parcelas`, `produtos_inclusos[]`, `condicao_aprovacao`.

**Estados.** Idem.

---

### 1.6 Listagem de Adesões

| Propriedade   | Valor                                             |
| ------------- | ------------------------------------------------- |
| **Rota**      | `GET /admin/comercial/adesoes`                    |
| **Persona**   | Admin                                             |
| **Propósito** | Gerenciar adesões de formandos, resolver exceções |

**Componentes.** DataTable com filtros por status/evento/turma/vencimento, badges para status, `<x-admin.action-menu>`.

**Ações.** Ver detalhe, cancelar (exige justificativa), gerar boleto manual, exportar.

**Dados.** `formando.nome`, `pacote.nome`, `status`, `valor_total_centavos`, `parcelas_abertas`, `ultima_movimentacao_at`.

**Estados.** Idem.

---

### 1.7 Detalhe da Adesão

| Propriedade   | Valor                                                        |
| ------------- | ------------------------------------------------------------ |
| **Rota**      | `GET /admin/comercial/adesoes/{id}`                          |
| **Persona**   | Admin                                                        |
| **Propósito** | Auditoria completa da adesão, snapshot, parcelas, pagamentos |

**Componentes.** `<x-admin.info-block>`, tabela de parcelas, trilha `<x-admin.timeline>` de `activity_log`.

**Ações.** Cancelar com motivo, reativar (se aplicável), gerar PDF do termo, reenviar recibo.

**Dados.** `snapshot_comercial` (read-only JSON pretty-printed), parcelas com status, pagamentos e webhooks vinculados.

**Estados.** Idem.

---

### 1.8 Gestão de Convites

| Propriedade   | Valor                                              |
| ------------- | -------------------------------------------------- |
| **Rota**      | `GET /admin/convites`                              |
| **Persona**   | Admin, Comissão (escopo turma)                     |
| **Propósito** | Ver funil, reemitir, cancelar, transferir convites |

**Componentes.** DataTable com filtros `status`, `tipo`, `lote`; `<x-admin.funnel-chart>` mini; `<x-admin.action-menu>`.

**Ações.** Reemitir token, cancelar, transferir, visualizar histórico RSVP, exportar.

**Dados.** `codigo`, `convidado_nome`, `status`, `tipo`, `entregue_at`, `visualizado_at`, `confirmado_at`.

**Estados.** Idem + estado **`token_revogado`** com badge cinza.

---

### 1.9 Configuração de Mapa de Mesas

| Propriedade   | Valor                                                 |
| ------------- | ----------------------------------------------------- |
| **Rota**      | `GET /admin/seating/mapas/{id}`                       |
| **Persona**   | Admin, Comissão (se permitido)                        |
| **Propósito** | Desenhar setores, mesas e assentos; bloquear técnicos |

**Componentes.** Canvas interativo (SortableJS + SVG custom), `<x-admin.seating-palette>`, inspector lateral.

**Ações.** Adicionar setor/mesa/assento, editar capacidade, bloquear, clonar template.

**Dados.** Posição (x, y), rotação, capacidade, status (ativo/bloqueada).

**Estados.**

- `loading`: skeleton do canvas.
- `empty`: canvas vazio + CTA "Adicionar primeiro setor".
- `error`: banner + preserva trabalho local.
- `success`: canvas renderizado.

---

### 1.10 Reservas do Evento

| Propriedade   | Valor                                               |
| ------------- | --------------------------------------------------- |
| **Rota**      | `GET /admin/seating/reservas`                       |
| **Persona**   | Admin, Operação                                     |
| **Propósito** | Ver todas reservas, forçar troca, liberar, bloquear |

**Componentes.** DataTable + badge status + ação contextual com justificativa obrigatória.

**Ações.** Forçar troca, liberar, bloquear assento, exportar mapa (PDF).

**Dados.** `mesa.numero`, `assento.numero`, `formando.nome`, `status`, `hold_expires_at`, `confirmado_at`.

**Estados.** Idem.

---

### 1.11 Catálogo de Extras

| Propriedade   | Valor                                                      |
| ------------- | ---------------------------------------------------------- |
| **Rota**      | `GET /admin/extras/produtos`                               |
| **Persona**   | Admin                                                      |
| **Propósito** | CRUD de produtos extras, regras de estoque e elegibilidade |

**Componentes.** DataTable, modal com `<x-admin.eligibility-builder>` (regra JSONB declarativa).

**Ações.** CRUD, pausar venda, duplicar.

**Dados.** `nome`, `preco_centavos`, `estoque_tipo`, `estoque_qtd`, `abre_at`, `fecha_at`, `requer_aprovacao`.

**Estados.** Idem.

---

### 1.12 Pedidos Extras

| Propriedade   | Valor                             |
| ------------- | --------------------------------- |
| **Rota**      | `GET /admin/extras/pedidos`       |
| **Persona**   | Admin                             |
| **Propósito** | Aprovar/rejeitar/estornar pedidos |

**Componentes.** DataTable + filtros por status + modal de aprovação com motivo.

**Ações.** Aprovar, rejeitar, estornar (invalida convites derivados), reenviar link.

**Dados.** `formando`, `produto`, `qtd`, `valor_total`, `status`, `criado_at`.

**Estados.** Idem + estado **`aguardando_aprovacao`** com prioridade visual.

---

### 1.13 Pagamentos e Webhooks

| Propriedade   | Valor                                                     |
| ------------- | --------------------------------------------------------- |
| **Rota**      | `GET /admin/pagamentos`, `GET /admin/pagamentos/webhooks` |
| **Persona**   | Admin                                                     |
| **Propósito** | Auditoria financeira, reprocessar webhooks                |

**Componentes.** DataTable + filtros status/método/provider, `<x-admin.webhook-detail>` com payload JSON formatado.

**Ações.** Reprocessar webhook, marcar como `descartado`, exportar CSV.

**Dados.** `gateway_reference`, `provider`, `status`, `recebido_at`, `processado_at`, `tentativas`, `ultimo_erro`.

**Estados.** Idem + estado **`falhou`** com highlight vermelho.

---

### 1.14 Enquetes

| Propriedade   | Valor                         |
| ------------- | ----------------------------- |
| **Rota**      | `GET /admin/enquetes`         |
| **Persona**   | Admin                         |
| **Propósito** | CRUD e publicação de enquetes |

**Componentes.** DataTable, modal com `<x-admin.opcoes-builder>`, `<x-admin.elegibilidade-builder>`.

**Ações.** Criar, publicar, encerrar, ver resultado.

**Dados.** `tipo`, `status`, `janela`, `total_votos`.

**Estados.** Idem.

---

### 1.15 Usuários e ACL

| Propriedade   | Valor                                            |
| ------------- | ------------------------------------------------ |
| **Rota**      | `GET /admin/usuarios`                            |
| **Persona**   | Admin                                            |
| **Propósito** | Gerenciar admin/comissão/operação + roles Spatie |

**Componentes.** DataTable, modal com `<x-admin.role-picker>`, `<x-admin.permission-matrix>`.

**Ações.** Criar, atribuir roles, revogar tokens, inativar.

**Dados.** `nome`, `email`, `roles`, `ultimo_login_at`, `status`.

**Estados.** Idem.

---

### 1.16 Auditoria

| Propriedade   | Valor                    |
| ------------- | ------------------------ |
| **Rota**      | `GET /admin/auditoria`   |
| **Persona**   | Admin                    |
| **Propósito** | Consultar `activity_log` |

**Componentes.** DataTable + filtros por causer/subject/evento/data, `<x-admin.diff-viewer>`.

**Ações.** Filtrar, exportar, ver diff `attribute_changes`.

**Dados.** `causer_type`, `causer_id`, `subject_type`, `subject_id`, `event`, `properties`, `created_at`.

**Estados.** Idem.

---

### 1.17 Relatórios

| Propriedade   | Valor                                                |
| ------------- | ---------------------------------------------------- |
| **Rota**      | `GET /admin/relatorios`                              |
| **Persona**   | Admin                                                |
| **Propósito** | Emitir relatórios (CSV/Excel/PDF) via job assíncrono |

**Componentes.** Lista de tipos de relatório, `<x-admin.date-range>`, `<x-admin.job-status>`.

**Ações.** Solicitar geração (cai em `exports` queue), baixar pronto (URL assinada).

**Dados.** Histórico de relatórios com status e URL.

**Estados.**

- `loading`: "Processando… você receberá e-mail".
- `empty`: lista vazia.
- `error`: `failed_jobs` reportado no Horizon.
- `success`: link para download + expiração (URL assinada 5 min).

---

### 1.18 Operação — Check-in

| Propriedade   | Valor                               |
| ------------- | ----------------------------------- |
| **Rota**      | `GET /admin/operacao/checkin`       |
| **Persona**   | Operação                            |
| **Propósito** | Validar convidados no dia do evento |

**Componentes.** Câmera QR, input manual de código, `<x-admin.checkin-card>` com foto + status.

**Ações.** Ler QR, confirmar entrada (→ `inutilizado`), bloquear.

**Dados.** `codigo`, `convidado_nome`, `tipo`, `status`, `mesa`, `assento`.

**Estados.**

- `loading`: "Buscando…".
- `empty`: tela pronta para primeira leitura.
- `error`: convite não encontrado / já utilizado / de outro evento → banner vermelho + som.
- `success`: banner verde + som + "Próximo".

---

### Sumário Admin

| #    | Tela             | Rota                            | Persona principal       |
| ---- | ---------------- | ------------------------------- | ----------------------- |
| 1.1  | Login            | `/admin/login`                  | Admin/Comissão/Operação |
| 1.2  | Dashboard        | `/admin/dashboard`              | Admin/Comissão          |
| 1.3  | Listagem Eventos | `/admin/eventos`                | Admin/Comissão          |
| 1.4  | Detalhe Evento   | `/admin/eventos/{id}`           | Admin/Comissão          |
| 1.5  | Pacotes/Produtos | `/admin/comercial/pacotes`      | Admin                   |
| 1.6  | Listagem Adesões | `/admin/comercial/adesoes`      | Admin                   |
| 1.7  | Detalhe Adesão   | `/admin/comercial/adesoes/{id}` | Admin                   |
| 1.8  | Gestão Convites  | `/admin/convites`               | Admin/Comissão          |
| 1.9  | Mapa Mesas       | `/admin/seating/mapas/{id}`     | Admin                   |
| 1.10 | Reservas         | `/admin/seating/reservas`       | Admin/Operação          |
| 1.11 | Catálogo Extras  | `/admin/extras/produtos`        | Admin                   |
| 1.12 | Pedidos Extras   | `/admin/extras/pedidos`         | Admin                   |
| 1.13 | Pagamentos       | `/admin/pagamentos`             | Admin                   |
| 1.14 | Enquetes         | `/admin/enquetes`               | Admin                   |
| 1.15 | Usuários/ACL     | `/admin/usuarios`               | Admin                   |
| 1.16 | Auditoria        | `/admin/auditoria`              | Admin                   |
| 1.17 | Relatórios       | `/admin/relatorios`             | Admin                   |
| 1.18 | Check-in         | `/admin/operacao/checkin`       | Operação                |

---

## 2. Portal SPA (React)

Consome exclusivamente `api/v1` via Sanctum SPA cookie. Mobile-first. Rotas client-side com React Router.

### 2.1 Login

| Propriedade     | Valor                                                             |
| --------------- | ----------------------------------------------------------------- |
| **Rota client** | `/login`                                                          |
| **Rota API**    | `GET /sanctum/csrf-cookie` → `POST /api/v1/auth/login` (mode=spa) |
| **Persona**     | Formando                                                          |
| **Propósito**   | Autenticar formando                                               |

**Componentes.** Input email, senha (`<FormField>`), toggle senha, CTA "Entrar", link "Esqueci".

**Ações.** Submit, recuperar senha.

**Dados.** Nenhum pré-auth.

**Estados.** Idem padrão. Rate limit visível.

---

### 2.2 Home do Formando

| Propriedade     | Valor                                              |
| --------------- | -------------------------------------------------- |
| **Rota client** | `/`                                                |
| **Rota API**    | `GET /api/v1/me/eventos`, `GET /api/v1/me/adesoes` |
| **Persona**     | Formando                                           |
| **Propósito**   | Hub pessoal: eventos, adesões, próximos passos     |

**Componentes.** `<EventCard>`, `<AdesaoStatusBadge>`, `<NextActionBanner>`.

**Ações.** Abrir evento, ir para pagamento, emitir convite.

**Dados.** Lista de eventos ativos, status da adesão, próxima parcela, alertas.

**Estados.**

- `loading`: skeleton dos cards.
- `empty`: "Você ainda não aderiu a nenhum evento" + CTA.
- `error`: banner retry.
- `success`: cards clicáveis.

---

### 2.3 Wizard de Adesão

| Propriedade     | Valor                                                                    |
| --------------- | ------------------------------------------------------------------------ |
| **Rota client** | `/adesao/wizard` (7 etapas)                                              |
| **Rota API**    | `POST /api/v1/eventos/{ulid}/adesoes`, `POST /api/v1/pagamentos/intents` |
| **Persona**     | Formando                                                                 |
| **Propósito**   | Contratar pacote                                                         |

**Componentes.** `<WizardStepper>`, `<PackageCard>`, `<ParcelamentoSimulator>`, `<TermoAceite>`.

**Ações.** Navegar etapas, salvar rascunho, submit final, pagar entrada.

**Dados.** Pacotes disponíveis, simulação de parcelamento, termo em HTML, QR PIX.

**Estados.** Idem + estado **`rascunho_salvo`** com timestamp.

---

### 2.4 Financeiro — Extrato

| Propriedade     | Valor                            |
| --------------- | -------------------------------- |
| **Rota client** | `/financeiro`                    |
| **Rota API**    | `GET /api/v1/me/extrato`         |
| **Persona**     | Formando, Responsável Financeiro |
| **Propósito**   | Ver parcelas em aberto e pagas   |

**Componentes.** Lista agrupada por status (aberta, paga, vencida), `<InstallmentRow>`, `<PayButton>`.

**Ações.** Pagar, baixar comprovante PDF (queued).

**Dados.** `parcelas[]` com `numero`, `valor_centavos`, `vencimento`, `status`, `pagamento_at?`.

**Estados.** Idem.

---

### 2.5 Página de Pagamento

| Propriedade     | Valor                                                              |
| --------------- | ------------------------------------------------------------------ |
| **Rota client** | `/pagamentos/{intent_ulid}`                                        |
| **Rota API**    | `POST /api/v1/pagamentos/intents`, `GET /api/v1/pagamentos/{ulid}` |
| **Persona**     | Formando, Responsável                                              |
| **Propósito**   | Exibir QR PIX / boleto / cartão                                    |

**Componentes.** Tabs `<PaymentMethodTabs>`, `<PixQRCode>` grande, `<BoletoLinkCard>`, `<CardCheckoutFrame>` (iframe gateway).

**Ações.** Copiar código PIX, baixar boleto, pagar cartão, polling de status.

**Dados.** `gateway_reference`, QR Code base64, copia/cola PIX, link boleto.

**Estados.**

- `loading`: spinner central.
- `empty`: — (sempre tem dados após criar intent).
- `error`: erro do gateway com CTA retry.
- `success`: badge "Pagamento recebido" + redirect 3s.

---

### 2.6 Carteira de Convites

| Propriedade     | Valor                               |
| --------------- | ----------------------------------- |
| **Rota client** | `/convites`                         |
| **Rota API**    | `GET /api/v1/me/convites`           |
| **Persona**     | Formando                            |
| **Propósito**   | Ver funil pessoal, emitir, reemitir |

**Componentes.** `<CotaCard>` (disponível/usado/bloqueado), `<InvitesList>` com badge status, `<InviteForm>`.

**Ações.** Emitir unit, abrir modal de emissão em lote, reemitir, cancelar, copiar link, transferir.

**Dados.** Convites com `codigo`, `convidado_nome`, `status`, `entregue_at`.

**Estados.** Idem.

---

### 2.7 RSVP Público

| Propriedade     | Valor                                                              |
| --------------- | ------------------------------------------------------------------ |
| **Rota client** | `/convite/{token}`                                                 |
| **Rota API**    | `GET /api/v1/convite/{token}`, `POST /api/v1/convite/{token}/rsvp` |
| **Persona**     | Convidado                                                          |
| **Propósito**   | Ver convite e confirmar presença sem cadastro                      |

**Componentes.** `<InviteHero>` com foto/arte, `<EventoInfo>`, `<RsvpChoice>` (confirmar/recusar), `<AddToCalendar>`.

**Ações.** Confirmar, recusar, adicionar ao calendário (.ics), abrir mapa.

**Dados.** `evento` (nome, data, local, timezone), `convidado` (nome), `janela_rsvp`.

**Estados.**

- `loading`: skeleton do hero.
- `empty`: — (página pública sempre renderiza).
- `error` 404: "Convite não encontrado ou revogado" (texto genérico).
- `success`: após RSVP → "Presença registrada! Te esperamos em {data}".

---

### 2.8 Mapa de Mesas (cliente)

| Propriedade     | Valor                                                                                                  |
| --------------- | ------------------------------------------------------------------------------------------------------ |
| **Rota client** | `/eventos/{ulid}/mesas`                                                                                |
| **Rota API**    | `GET /api/v1/eventos/{ulid}/mesas/mapa?since=...`, `POST /reservas`, `POST /reservas/{ulid}/confirmar` |
| **Persona**     | Formando (convidado se permitido)                                                                      |
| **Propósito**   | Escolher assento                                                                                       |

**Componentes.** `<SeatingMap>` SVG interativo, `<SeatInspector>`, `<HoldTimer>` (contagem regressiva 5 min), `<ReservationConfirmModal>`.

**Ações.** Selecionar assento (POST reserva → hold), confirmar, liberar, trocar, zoom, filtrar por setor.

**Dados.** Estrutura do mapa (setores, mesas, assentos, status), reservas do ator, delta WS.

**Estados.**

- `loading`: skeleton.
- `empty`: "Mapa ainda não publicado" (antes de `abre_mesas_at`).
- `error`: banner retry + fallback sem WS (polling).
- `success`: mapa renderizado.
- **`hold_ativo`**: overlay com timer, CTA Confirmar/Liberar.
- **`hold_expirado`**: toast "Seu hold expirou, reserve novamente".

---

### 2.9 Catálogo de Extras

| Propriedade     | Valor                                                                |
| --------------- | -------------------------------------------------------------------- |
| **Rota client** | `/extras`                                                            |
| **Rota API**    | `GET /api/v1/eventos/{ulid}/extras/catalogo`, `POST /extras/pedidos` |
| **Persona**     | Formando                                                             |
| **Propósito**   | Comprar convites extras, upgrades                                    |

**Componentes.** `<ProductGrid>`, `<ProductCard>`, `<Cart>`, `<CheckoutDrawer>`.

**Ações.** Adicionar ao carrinho, alterar qtd, checkout (cria pedido + intent).

**Dados.** Produtos elegíveis com `preco_centavos`, `estoque_disponivel`, `requer_aprovacao`.

**Estados.** Idem + **`aguardando_aprovacao`** com feedback "Seu pedido foi enviado para aprovação".

---

### 2.10 Enquetes

| Propriedade     | Valor                                                                |
| --------------- | -------------------------------------------------------------------- |
| **Rota client** | `/enquetes`                                                          |
| **Rota API**    | `GET /api/v1/eventos/{ulid}/enquetes`, `POST /enquetes/{ulid}/votos` |
| **Persona**     | Formando, Comissão                                                   |
| **Propósito**   | Votar                                                                |

**Componentes.** `<PollCard>`, `<OptionList>` (radio/checkbox por tipo), `<ResultsBar>` se público.

**Ações.** Votar, alterar voto se permitido, ver resultados.

**Dados.** Enquetes publicadas, opções, voto do ator.

**Estados.** Idem + **`ja_votado`** e **`janela_fechada`**.

---

### 2.11 Perfil

| Propriedade     | Valor                                 |
| --------------- | ------------------------------------- |
| **Rota client** | `/perfil`                             |
| **Rota API**    | `GET /api/v1/me`, `PATCH /api/v1/me`  |
| **Persona**     | Formando                              |
| **Propósito**   | Editar dados pessoais, senha, devices |

**Componentes.** `<ProfileForm>`, `<PasswordChange>`, `<DevicesList>`.

**Ações.** Atualizar dados, trocar senha, revogar tokens (devices).

**Dados.** `nome`, `email` (read-only), `telefone`, `personal_access_tokens[]`.

**Estados.** Idem.

---

### Sumário Portal SPA

| #    | Tela              | Rota client             | Persona               |
| ---- | ----------------- | ----------------------- | --------------------- |
| 2.1  | Login             | `/login`                | Formando              |
| 2.2  | Home              | `/`                     | Formando              |
| 2.3  | Adesão Wizard     | `/adesao/wizard`        | Formando              |
| 2.4  | Financeiro        | `/financeiro`           | Formando, Responsável |
| 2.5  | Pagamento         | `/pagamentos/{ulid}`    | Formando, Responsável |
| 2.6  | Carteira Convites | `/convites`             | Formando              |
| 2.7  | RSVP Público      | `/convite/{token}`      | Convidado             |
| 2.8  | Mapa Mesas        | `/eventos/{ulid}/mesas` | Formando              |
| 2.9  | Extras            | `/extras`               | Formando              |
| 2.10 | Enquetes          | `/enquetes`             | Formando, Comissão    |
| 2.11 | Perfil            | `/perfil`               | Formando              |

---

## 3. Mobile RN (F8 futuro)

Expo Router + TanStack Query. Login via `mode=token`. Subset do portal otimizado para bolso.

### 3.1 Login Mobile

| Propriedade   | Valor                                                      |
| ------------- | ---------------------------------------------------------- |
| **Rota**      | `/(auth)/login`                                            |
| **API**       | `POST /api/v1/auth/login` com `mode=token` + `device_name` |
| **Persona**   | Formando                                                   |
| **Propósito** | Autenticar e guardar token em Keychain/Keystore            |

**Componentes.** `<LoginScreen>`, inputs nativos, biometric prompt (opcional).

**Ações.** Submit, habilitar biometria.

**Dados.** Email/senha pré-auth.

**Estados.** Idem.

---

### 3.2 Home Mobile

| Propriedade   | Valor                    |
| ------------- | ------------------------ |
| **Rota**      | `/(tabs)/home`           |
| **API**       | `GET /api/v1/me/eventos` |
| **Persona**   | Formando                 |
| **Propósito** | Dashboard condensado     |

**Componentes.** `<QuickActions>`, `<EventCard>`, `<NextParcelaCard>`.

**Ações.** Tap em ações rápidas (pagar, convites, mesa).

**Dados.** Próxima parcela, RSVP pendentes, reserva atual.

**Estados.** Idem.

---

### 3.3 Carteira de Convites Mobile

| Propriedade   | Valor                       |
| ------------- | --------------------------- |
| **Rota**      | `/(tabs)/convites`          |
| **API**       | `GET /api/v1/me/convites`   |
| **Persona**   | Formando                    |
| **Propósito** | Emitir e gerenciar no bolso |

**Componentes.** `<InviteListItem>`, `<EmitSheet>`.

**Ações.** Emitir nominal, compartilhar link via WhatsApp nativo, reemitir.

**Dados.** Convites com status.

**Estados.** Idem.

---

### 3.4 Mapa de Mesas Mobile (simplificado)

| Propriedade   | Valor                                          |
| ------------- | ---------------------------------------------- |
| **Rota**      | `/(tabs)/mesas`                                |
| **API**       | `GET /mapa`, `POST /reservas/{ulid}/confirmar` |
| **Persona**   | Formando                                       |
| **Propósito** | Confirmar assento já pré-reservado no web      |

**Componentes.** `<SeatBadge>`, `<ConfirmCTA>`.

**Ações.** Confirmar hold, liberar.

**Dados.** Reserva atual do ator.

**Estados.** Idem + hold timer em destaque.

---

### 3.5 Push & Notifications

| Propriedade   | Valor                                                           |
| ------------- | --------------------------------------------------------------- |
| **Rota**      | — (background)                                                  |
| **API**       | Registra `expo_push_token` em endpoint `/api/v1/me/push-tokens` |
| **Persona**   | Formando                                                        |
| **Propósito** | Receber avisos                                                  |

**Ações.** Permitir notifs, desativar por tipo.

**Dados.** Categorias de notif.

**Estados.** `permitido`, `negado`, `pendente`.

---

### 3.6 Check-in do Dia (autoatendimento)

| Propriedade   | Valor                                |
| ------------- | ------------------------------------ |
| **Rota**      | `/(tabs)/meu-evento`                 |
| **API**       | `GET /api/v1/me/evento-atual`        |
| **Persona**   | Formando                             |
| **Propósito** | Mostrar QR do formando para recepção |

**Componentes.** `<QRCodeDisplay>` full-screen, dados da mesa.

**Ações.** Brilho máximo, offline-cache do QR.

**Dados.** Token de check-in, mesa, assento.

**Estados.** Idem.

---

### Sumário Mobile

| #   | Tela       | Rota                 | Persona  |
| --- | ---------- | -------------------- | -------- |
| 3.1 | Login      | `/(auth)/login`      | Formando |
| 3.2 | Home       | `/(tabs)/home`       | Formando |
| 3.3 | Convites   | `/(tabs)/convites`   | Formando |
| 3.4 | Mesas      | `/(tabs)/mesas`      | Formando |
| 3.5 | Push       | (background)         | Formando |
| 3.6 | Meu evento | `/(tabs)/meu-evento` | Formando |

---

## 4. Catálogo de estados

Todo bloco de dados precisa contemplar **pelo menos** estes 4 estados:

| Estado    | Gatilho típico                 | UI esperada                                   |
| --------- | ------------------------------ | --------------------------------------------- |
| `loading` | Requisição em voo              | Skeleton + desabilitar CTAs                   |
| `empty`   | Requisição 200 com lista vazia | Ilustração + CTA primário                     |
| `error`   | 4xx/5xx ou timeout             | Banner com mensagem do envelope §2.11 + retry |
| `success` | Dados prontos                  | Conteúdo normal                               |

Estados adicionais por contexto:

| Estado extra           | Contextos                               |
| ---------------------- | --------------------------------------- |
| `janela_fechada`       | Adesão, RSVP, Seating, Extras, Enquetes |
| `hold_ativo`           | Seating (com timer)                     |
| `hold_expirado`        | Seating                                 |
| `token_revogado`       | Convites                                |
| `aguardando_aprovacao` | Pedidos extras                          |
| `rascunho_salvo`       | Wizard de adesão                        |
| `offline`              | Mobile (usa cache local)                |

---

## 5. Referências cruzadas

- Fluxos completos: [`user-flows.md`](./user-flows.md).
- Personas que usam cada tela: [`journeys-personas.md`](./journeys-personas.md).
- RF associados: [`SRS.md`](./SRS.md) — seção 3.1.
- Endpoints: [`../prd/PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) §2.
- Componentes Blade Admin: CLAUDE.md §11 e `docs/INSPINIA-CATALOGO-COMPONENTES.md`.
