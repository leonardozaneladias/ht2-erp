# Mapeamento de Squad por Fase — Portal ArtFinal

Baseado em `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §14.

---

## F1 — Fundação de domínio e API-ready (34 SP)

**Objetivo:** `api/v1` publicada; guards operacionais; migrations A+B; policies base.

### Skills obrigatórias

| Skill | Responsabilidade |
|---|---|
| `laravel-best-practices` | Padrões gerais: strict_types, type hints, PSR-12 |
| `laravel-specialist` | Setup Laravel 13: guards, sanctum, providers |
| `laravel-models` | Models base: Organizacao, Instituicao, Curso, Turma, Evento, Formando |
| `laravel-services` | Services de domínio; RateLimiterServiceProvider, GatewayServiceProvider |
| `laravel-routing` | routes/api/v1.php e routes/webhook.php skeleton |
| `laravel-validation` | FormRequests base; middleware de validação |
| `laravel-security` | Sanctum, CORS, CSRF, middleware de auth |
| `laravel-enums` | Enums base: StatusAdesao, StatusParcela, PerfilAtor, OrigemAtor |
| `laravel-dtos` | Data classes: NovaAdesaoData, AdesaoResultData, PaginatedResponseData |
| `laravel-actions` | Actions skeleton: CriarAdesaoAction, GerarParcelasAction |
| `laravel-exceptions` | DomainException, InvariantViolationException |
| `pest-testing` | Pest arch tests, testes de middleware |
| `php-best-practices` | PHP 8.4: readonly, fibers, type system |

### Skills opcionais/situacionais

| Skill | Quando usar |
|---|---|
| `laravel-multi-tenancy` | Se decidir por tenant isolation no modelo de dados |
| `laravel-owasp-security` | Ao configurar rate limiting e headers de segurança |
| `superpowers-laravel:migrations-and-factories` | Ao escrever migrations A+B e factories base |
| `adr-skill` | Para registrar decisão de ULID vs UUID, guards, etc. |

### BMAD agents

| Agent | Quando |
|---|---|
| `scrum-master` | Quebrar F1 em stories; estimar SP |
| `developer` | Implementar cada story de F1 |
| `bmad-orchestrator` | Status geral, routing entre stories |

### Entregáveis F1

- `HasUlid` trait + `Support\Ulid` + `CorrelationContext`
- Middlewares: AttachRequestId, ResolveConviteToken, EnsureSanctumAbility, IdempotencyKeyGuard, RateLimitByActor
- `RateLimiterServiceProvider`, `GatewayServiceProvider`, `DomainEventServiceProvider`, `AuthServiceProvider`
- Migrations blocos A (identidade) + B (cadastro)
- `GET /api/v1/me` → 401/200 ✓
- CI: pint + phpstan + pest + prettier

---

## F2 — Admin estrutural (40 SP)

**Objetivo:** CRUD completo org/instituição/curso/turma/evento/pacote/produto; ACL; dashboard.

### Skills obrigatórias

| Skill | Responsabilidade |
|---|---|
| `livewire-development` | Componentes Livewire para todos os CRUDs |
| `tailwindcss-development` | Estilização com Tailwind v4 + Inspinia |
| `laravel-controllers` | Controllers Livewire enxutos |
| `laravel-permission-development` | Spatie Permission: roles (admin, comissao) e permissions |
| `laravel-policies` | Policies para cada entidade |
| `laravel-models` | Models: Pacote, Produto, AdesaoComercial, AdminUser |
| `laravel-actions` | Actions de CRUD (CreateOrganizacaoAction, etc.) |
| `laravel-validation` | Form Requests para cada CRUD |
| `pest-testing` | Feature tests para cada CRUD e policy |

### Skills opcionais/situacionais

| Skill | Quando usar |
|---|---|
| `superpowers-laravel:blade-components-and-layouts` | Ao criar componentes Blade reutilizáveis (kpi-card, data-table) |
| `superpowers-laravel:performance-eager-loading` | Ao otimizar queries de listagem |
| `laravel-query-builders` | Para filtros e buscas complexas nas listagens |
| `debug-using-debugbar` | Diagnóstico de N+1 em listas |
| `laravel-exceptions` | Exceções de negócio (quota, conflito) |

### BMAD agents

| Agent | Quando |
|---|---|
| `scrum-master` | Planejar stories de CRUD por entidade |
| `developer` | Implementar CRUD + testes |
| `ux-designer` | Wireframes e fluxo do admin Inspinia |
| `product-manager` | Validar requisitos de ACL com cliente |

### Entregáveis F2

- Login admin + reset
- CRUD: Organizacao, Instituicao, Curso, Turma, Evento, Pacote, Produto, AdesaoComercial
- Gestão de usuários admin + permissões Spatie
- Dashboard com KPIs básicos (cards, gráficos Apex)
- Separação clara comissao/admin por role

---

## F3 — Cliente web React SPA (34 SP)

**Objetivo:** SPA React consome api/v1; auth Sanctum SPA; dashboard formando.

### Skills obrigatórias

| Skill | Responsabilidade |
|---|---|
| `react-best-practices` | Padrões React 18/19 no contexto SPA |
| `react-components` | Componentização da SPA do formando |
| `react-state-management` | Estado global: Zustand ou Context |
| `tailwindcss-development` | Design system do portal formando |
| `laravel-api` | API Resources e paginação para SPA |
| `laravel-security` | CSRF cookie, Sanctum SPA auth |
| `pest-testing` | Testes de integração API ↔ SPA |

### Skills opcionais/situacionais

| Skill | Quando usar |
|---|---|
| `react-patterns` | Padrões avançados (compound, render props) |
| `react-useeffect` | Sincronização e side effects no SPA |
| `vercel-react-best-practices` | Se o SPA for hospedado na Vercel |
| `ui-ux-pro-max` | Polimento de UX do portal |
| `react-ui-patterns` | Patterns de lista, filtro, paginação |
| `laravel-inertia-react` | Se optar por Inertia em vez de SPA puro |

### BMAD agents

| Agent | Quando |
|---|---|
| `scrum-master` | Planejar stories do SPA |
| `developer` | Implementar componentes e integração |
| `ux-designer` | UI do portal formando (mobile-first) |
| `product-manager` | Priorização de features do formando |

### Entregáveis F3

- Setup React em resources/spa/ (ou repo separado, a decidir)
- Auth via csrf-cookie + Sanctum
- Dashboard do formando com dados reais
- Extrato de parcelas
- Carteira de convites (mock)
- Design system mínimo

---

## F4 — Convites e RSVP (28 SP)

**Objetivo:** Emissão, envio, token, RSVP, contadores. Cobertura ≥ 80%.

### Skills obrigatórias

| Skill | Responsabilidade |
|---|---|
| `laravel-models` | Models: LoteConvite, Convite, RsvpHistorico, CotaRegra |
| `laravel-actions` | EmitirConviteAction, EmitirLoteConvitesAction, CancelarConviteAction, TransferirConviteAction, RegistrarRsvpAction, AlterarRsvpAction |
| `laravel-jobs` | EnviarConviteEmailJob, EnviarReminderRsvpJob |
| `laravel-services` | Lógica de cota e distribuição de convites |
| `laravel-enums` | StatusConvite, TipoConvite, StatusRsvp |
| `laravel-dtos` | NovoConviteData, ConviteResultData |
| `pest-testing` | Testes de ação, job, RSVP público, concorrência de emissão |
| `configuring-horizon` | Fila `emails` para EnviarConviteEmailJob |

### Skills opcionais/situacionais

| Skill | Quando usar |
|---|---|
| `laravel-state-machines` | Para fluxo de estado do convite (criado→enviado→aceito→cancelado) |
| `laravel-exceptions` | Exceções: CotaEsgotadaException |
| `superpowers-laravel:transactions-and-consistency` | Ao emitir lote (idempotência) |
| `laravel-value-objects` | Token criptográfico como Value Object |

### BMAD agents

| Agent | Quando |
|---|---|
| `scrum-master` | Planejar stories por bounded context (emissão, RSVP, cota) |
| `developer` | Implementar actions + jobs + API |

### Entregáveis F4

- Token: `bin2hex(random_bytes(32))`
- Emissão em lote de 500 convites ≤ 60s
- RSVP público funcional via token
- Contadores atualizados em tempo real
- UI admin (Livewire) para listagem/emissão
- UI formando (React) para visualização

---

## F5 — Seating (34 SP) — fase crítica

**Objetivo:** Hold 5min, unique parcial, job de expiração, testes de concorrência. 0% conflito em 1.000 tentativas simultâneas.

### Skills obrigatórias

| Skill | Responsabilidade |
|---|---|
| `laravel-models` | MapaMesa, Setor, Mesa, Assento, ReservaAssento, ReservaHistorico |
| `laravel-actions` | ReservarAssentoAction, ConfirmarAssentoAction, LiberarAssentoAction, ExpirarHoldAssentoAction, TrocarAssentoAction |
| `laravel-services` | HoldService, DisponibilidadeService |
| `laravel-state-machines` | Máquina de estados da reserva: disponivel→hold→confirmado→liberado |
| `laravel-jobs` | ExpirarHoldsJob (everyMinute), PublicarAtualizacaoMapaJob |
| `configuring-horizon` | Fila `gateway` para jobs críticos de seating |
| `superpowers-laravel:transactions-and-consistency` | Transação curta + Redis lock |
| `superpowers-laravel:performance-caching` | Cache do mapa de mesas + invalidação por evento |
| `pest-testing` | Testes de concorrência obrigatórios |
| `laravel-exceptions` | AssentoIndisponivelException, HoldExpiradoException |

### Skills opcionais/situacionais

| Skill | Quando usar |
|---|---|
| `laravel-query-builders` | Queries de disponibilidade com unique parcial |
| `superpowers-laravel:queues-and-horizon` | Tuning de filas para ExpirarHoldsJob |
| `debug-using-debugbar` | Diagnóstico de performance do mapa |
| `superpowers-laravel:data-chunking-large-datasets` | Se mapa tiver >1.000 assentos |

### BMAD agents

| Agent | Quando |
|---|---|
| `scrum-master` | Planejar stories críticas com critérios de concorrência |
| `developer` | Implementar com Redis lock + unique parcial |
| `ux-designer` | UI do mapa interativo (React) com hold visual + timer |

### Entregáveis F5

- Unique parcial no banco + Redis lock + idempotency key
- P95 reserva ≤ 700ms
- Timer visual de hold no React SPA
- UI admin de desenho do mapa (Livewire)
- Testes de carga: 1.000 tentativas simultâneas sem conflito

---

## F6 — Extras, pagamentos e enquetes (34 SP)

**Objetivo:** Webhook idempotente, pedido extra → convites derivados ≤ 30s, voto único.

### Skills obrigatórias

| Skill | Responsabilidade |
|---|---|
| `laravel-models` | ProdutoExtra, PedidoExtra, PedidoExtraItem, Enquete, OpcaoEnquete, Voto, Pagamento |
| `laravel-actions` | CriarPedidoExtraAction, AprovarPedidoExtraAction, ConfirmarPagamentoExtraAction, EstornarPedidoExtraAction, IniciarPagamentoAction, ProcessarWebhookPagamentoAction, PublicarEnqueteAction, RegistrarVotoAction |
| `laravel-services` | Saloon connector Itaú, ReconciliarPagamentosService |
| `laravel-jobs` | ProcessarWebhookPagamentoJob, ReconciliarPagamentosJob, GerarRelatorioExcelJob, GerarComprovantePagamentoJob |
| `laravel-exceptions` | WebhookInvalidoException; domínio de pagamento |
| `laravel-enums` | StatusPedidoExtra, StatusPagamento, TipoEnquete, StatusEnquete |
| `laravel-value-objects` | IdempotencyKey como Value Object |
| `superpowers-laravel:transactions-and-consistency` | Webhook idempotente: reprocessamento 10× sem efeito duplo |
| `configuring-horizon` | Filas `gateway` (alta), `webhooks` (alta), `exports` (baixa) |
| `pest-testing` | Testes de webhook idempotente; voto único por ator |

### Skills opcionais/situacionais

| Skill | Quando usar |
|---|---|
| `laravel-state-machines` | Fluxo de estado do pagamento e pedido extra |
| `superpowers-laravel:http-client-resilience` | Saloon connector com retry e circuit breaker |
| `laravel-query-builders` | Queries de reconciliação financeira |

### BMAD agents

| Agent | Quando |
|---|---|
| `scrum-master` | Planejar stories por bounded context (gateway, extras, enquetes) |
| `developer` | Implementar webhook idempotente e Saloon |

### Entregáveis F6

- Saloon connector Itaú (boleto, PIX, cartão)
- Webhook reprocessado 10× sem efeito duplo
- Pedido extra pago → convites derivados em ≤ 30s
- Voto único por ator (unique no banco + check em Action)
- Export Excel de relatórios financeiros

---

## F7 — Hardening e observabilidade (21 SP)

**Objetivo:** Sentry, Pulse dashboards, alertas, audit LGPD.

### Skills obrigatórias

| Skill | Responsabilidade |
|---|---|
| `pulse-development` | Dashboards Pulse custom (filas, latência, erros) |
| `laravel-security` | Headers de segurança, HSTS, sanitização |
| `laravel-owasp-security` | Audit completo OWASP Top 10 |
| `laravel-security-audit` | Code review de segurança |
| `superpowers-laravel:exception-handling-and-logging` | Sentry integration, log estruturado |
| `configuring-horizon` | Alertas LongWaitDetected, métricas de wait |
| `pest-testing` | Testes de segurança: IDOR, mass assignment, rate limit |

### Skills opcionais/situacionais

| Skill | Quando usar |
|---|---|
| `debug-using-debugbar` | Diagnóstico final de performance antes do go-live |
| `laravel-multi-tenancy` | Se tenancy for activado nesta fase |

### BMAD agents

| Agent | Quando |
|---|---|
| `scrum-master` | Checklist de hardening por módulo |
| `developer` | Implementar anonimização LGPD e policies de audit |

### Entregáveis F7

- Sentry configurado e enviando alertas
- Pulse dashboards com métricas de filas e latência
- Job de anonimização LGPD
- Policy LGPD documentada
- Audit trail completo via spatie/activitylog

---

## F8 — Mobile MVP (34 SP)

**Objetivo:** React Native: login, carteira de convites, RSVP, seating simplificado, push.

### Skills obrigatórias

| Skill | Responsabilidade |
|---|---|
| `react-best-practices` | Padrões React Native adaptados |
| `react-state-management` | Estado global no RN (Zustand) |
| `laravel-api` | API Resources extras para RN; push tokens |
| `laravel-security` | Token refresh, revogação Sanctum RN |
| `pest-testing` | Testes de API para fluxos mobile |

### Skills opcionais/situacionais

| Skill | Quando usar |
|---|---|
| `laravel-jobs` | Job de push notification (fila `notifications`) |
| `react-components` | Componentes compartilháveis RN/Web |

### BMAD agents

| Agent | Quando |
|---|---|
| `scrum-master` | Planejar MVP mobile por tela |
| `developer` | Implementar telas RN |
| `ux-designer` | UX mobile (gestures, viewport, safe area) |

### Entregáveis F8

- Login + refresh token RN
- Carteira de convites
- RSVP por token
- Seating simplificado (seleção de mesa, sem hold timer completo)
- Push notification via FCM
