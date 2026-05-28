---
title: Software Architecture Document (arc42) — Portal ArtFinal v2 Backend API v1
version: 1.0.0
date: 2026-04-17
status: proposed
authors:
    - Equipe de Arquitetura Portal ArtFinal
audience:
    - Engenharia Laravel
    - QA
    - SRE / DevOps
    - Produto (leitura)
based_on:
    - docs/prd/PLANEJAMENTO_BACKEND_APIV1.md
    - docs/prd/PRD_v4.md
    - docs/prd/ARQUITETURA_DETALHADA.md
    - CLAUDE.md
---

# Software Architecture Document (arc42) — Portal ArtFinal v2

> Template: **arc42 v8 (PT-BR)**. Este documento descreve a arquitetura do backend API v1 do Portal ArtFinal. Todas as decisões refletem, e não substituem, o Planejamento Técnico (`docs/prd/PLANEJAMENTO_BACKEND_APIV1.md`). Onde houver divergência, o Planejamento é fonte-de-verdade e este SAD deve ser atualizado.

---

## 1. Introdução e objetivos

### 1.1 Visão geral do produto

O **Portal ArtFinal v2** é uma plataforma de gestão ponta-a-ponta de eventos de formatura: cadastro institucional (organizações, instituições, cursos, turmas), adesão comercial (pacotes, produtos, parcelamento, pagamento), emissão de convites nominais/transferíveis, RSVP, seating (mapas de mesas/assentos com hold concorrente), extras, enquetes/votações, relatórios e auditoria. O sistema se materializa em **três faces**:

1. **Admin (backoffice interno)** — Blade + Livewire 3 + Inspinia/Tailwind 4. Servido pelo mesmo monólito.
2. **Portal Web (cliente do formando e comissão)** — SPA React que consome `api/v1` via Sanctum stateful.
3. **App Mobile (formando)** — React Native/Expo que consome `api/v1` via Sanctum tokens.

Existe ainda uma face de **integrações**: webhooks de pagamento (Itaú, mock) e notificações outbound (e-mail, push, SMS).

### 1.2 Objetivos essenciais

| ID  | Objetivo                                                                                                            | Métrica de sucesso                                                            |
| --- | ------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| G1  | **API-first**: contrato estável para React Web e React Native desde F1 (§14 do Planejamento).                       | `api/v1` publicada no fim da F1; OpenAPI disponível em `/docs/api.json`.      |
| G2  | **Zero double-booking** em seating sob carga real.                                                                  | 0% de conflito em 1.000 tentativas simultâneas no mesmo assento (§14 F5).     |
| G3  | **Idempotência forte** em pagamentos e webhooks.                                                                    | 10× replays do mesmo webhook → 1 efeito (§10.4 do Planejamento).              |
| G4  | **Core independente da camada HTTP**.                                                                               | Pest arch tests garantem que `App\Actions` não importa `Illuminate\Http\*`.   |
| G5  | **Snapshots imutáveis** em toda entidade transacional confirmada (adesão, convite, reserva, pedido extra).          | Todo registro confirmado tem `snapshot_*` preenchido e não é mutável por UPD. |
| G6  | **Observabilidade ponta-a-ponta**: reconstrução do fluxo `convite → RSVP → reserva → pagamento` via correlation_id. | Query §12.4 retorna timeline em < 200 ms para qualquer `correlation_id`.      |

### 1.3 Stakeholders

| Papel                    | Expectativa principal                                                                 |
| ------------------------ | ------------------------------------------------------------------------------------- |
| Formando                 | Fluxo de adesão, RSVP, reserva de assento, extras e carteira de convites sem fricção. |
| Comissão de formatura    | Visibilidade e gestão parcial do evento, sem privilégios de admin.                    |
| Equipe comercial (admin) | CRUDs rápidos, relatórios confiáveis, controle financeiro.                            |
| Engenharia / Arquitetura | Código manutenível, testado, com fronteiras de contexto claras.                       |
| SRE / DevOps             | Observabilidade (Horizon, Pulse, Sentry), scaling horizontal, filas sem backlog.      |
| Provedor de pagamento    | Webhooks recebidos com assinatura HMAC e confirmados em ≤ 30 s.                       |

### 1.4 Escopo técnico (IN / OUT)

**IN (MVP):** identidade e acesso, cadastro (organização/instituição/curso/turma/evento), adesão comercial, parcelamento, convites, RSVP, seating (hold 5 min), extras, pagamentos via Itaú, enquetes, relatórios, auditoria, e-mail transacional, push (Expo).

**OUT (roadmap):** GraphQL, multi-tenant real com banco-por-tenant, CDN de mídia por cliente, multi-região ativo-ativo, app mobile com realtime (WS), integração com ERP, SSO externo (Google/Microsoft) para formandos.

---

## 2. Restrições (Constraints)

### 2.1 Restrições técnicas

| Restrição              | Valor / Regra                                                                                                 |
| ---------------------- | ------------------------------------------------------------------------------------------------------------- |
| Linguagem              | **PHP 8.4** — `declare(strict_types=1)` obrigatório em 100% dos arquivos (§0.7 Planejamento, CLAUDE.md §7.7). |
| Framework              | **Laravel 13**.                                                                                               |
| Banco                  | **PostgreSQL 16** (TIMESTAMPTZ, JSONB, unique parcial, CHECK constraints).                                    |
| Cache / Sessão / Filas | **Redis**.                                                                                                    |
| Filas                  | **Horizon 5**.                                                                                                |
| ORM                    | Eloquent. Raw queries só com binding explícito.                                                               |
| ID público             | **ULID CHAR(26)**. BIGINT interno nunca vaza em URL, token ou resposta (§2.7 Planejamento).                   |
| Valores monetários     | **INTEGER centavos**. Nunca float. Exibição via `MoneyFormatter` (CLAUDE.md §7.3).                            |
| Tempo                  | `TIMESTAMPTZ` no banco; ISO 8601 em JSON.                                                                     |
| Versionamento API      | Prefixo **`api/v1`** desde F1. Breaking change → `api/v2` (§2.3).                                             |
| Idempotência           | Header `X-Idempotency-Key` + cache Redis + unique constraint em DB (§2.9 e §5.1).                             |
| Qualidade              | Pint + PHPStan level 6 + Pest + Prettier bloqueando merge.                                                    |
| Docker                 | Laradock no macOS para dev; imagens PHP-FPM + Nginx para prod.                                                |

### 2.2 Restrições organizacionais

- **Equipe pequena** (≤ 5 devs na F1). Monólito modular preferível a microservices (§3.1 ARQUITETURA_DETALHADA).
- **Deploy único**: um pipeline, um processo PHP-FPM, um Horizon, uma base PG.
- **Idioma:** 100% PT-BR em variáveis de negócio, mensagens, documentação (CLAUDE.md §6).
- **Guard rails:** Husky + lint-staged + Pest arch tests bloqueiam violações estruturais.

### 2.3 Restrições políticas

- **LGPD**: coleta mínima de dados de convidado, retenção 90 dias pós-evento, anonimização automática via `AnonimizarDadosPosEventoJob` (§11.10 Planejamento).
- **PCI-DSS (indireto)**: nenhum dado de cartão persiste no sistema; apenas `gateway_reference` do provedor.

---

## 3. Contexto e escopo

### 3.1 Contexto de negócio (C4 Level 1)

```mermaid
C4Context
title Contexto de negócio — Portal ArtFinal v2

Person(formando, "Formando", "Aluno que adere ao pacote, emite convites, reserva mesa, compra extras.")
Person(responsavel, "Responsável financeiro", "Pai/mãe que eventualmente paga parcelas.")
Person(convidado, "Convidado", "Acessa por token mágico para RSVP.")
Person(comissao, "Comissão de turma", "Formandos eleitos com privilégios parciais.")
Person(admin, "Admin ArtFinal", "Operador da empresa organizadora.")

System(portalart, "Portal ArtFinal v2", "Plataforma de gestão de formaturas (admin + portal + mobile).")

System_Ext(itau, "Gateway Itaú", "Boleto, PIX, cartão. Webhooks HMAC.")
System_Ext(email, "Provedor de e-mail", "SES/SMTP transacional.")
System_Ext(push, "Expo Push", "Notificações iOS/Android.")
System_Ext(s3, "Armazenamento S3", "Comprovantes, termos PDF, exports Excel.")
System_Ext(sentry, "Sentry", "Error tracking.")

Rel(formando, portalart, "Adere, paga, emite convite, reserva mesa")
Rel(responsavel, portalart, "Paga parcelas")
Rel(convidado, portalart, "Abre link do convite, faz RSVP", "token mágico")
Rel(comissao, portalart, "Consulta RSVP da turma, gere enquetes")
Rel(admin, portalart, "Backoffice: CRUDs, relatórios, reconciliação")

Rel(portalart, itau, "Cria cobrança, recebe webhook", "HTTPS/HMAC")
Rel(portalart, email, "Envia e-mail transacional", "SMTP")
Rel(portalart, push, "Envia push", "HTTPS")
Rel(portalart, s3, "Upload/download com URL assinada", "HTTPS")
Rel(portalart, sentry, "Envia exceções", "HTTPS")
```

### 3.2 Contexto técnico

| Canal de entrada              | Protocolo                        | Autenticação                                    | Observações                                          |
| ----------------------------- | -------------------------------- | ----------------------------------------------- | ---------------------------------------------------- |
| React Web (SPA)               | HTTPS → `api/v1`                 | **Sanctum stateful** (cookie `laravel_session`) | Mesmo domínio raiz. CSRF via `/sanctum/csrf-cookie`. |
| React Native (Mobile)         | HTTPS → `api/v1`                 | **Sanctum token** (`Authorization: Bearer`)     | `abilities` derivadas de permissions Spatie.         |
| Convidado (público)           | HTTPS → `api/v1/convite/{token}` | Token mágico (middleware `ResolveConviteToken`) | Rate limit por IP (§2.10).                           |
| Admin / Comissão (backoffice) | HTTPS → rotas `web`              | Guards `admin` / `web`                          | Blade + Livewire; compartilha Actions com API.       |
| Webhooks (Itaú, mock)         | HTTPS → `webhooks/*`             | **HMAC-SHA256** de `X-Signature`                | Sem CSRF; idempotência via `webhook_eventos`.        |

---

## 4. Estratégia de solução

### 4.1 Decisões-chave (resumo executivo)

1. **Monólito modular em Laravel 13** com bounded contexts mapeados em namespaces (`App\Actions\<Contexto>`, `App\Models\<Contexto>`). Ver `ADR-0002`.
2. **API-first**: `api/v1` é a interface oficial de todos os clientes externos. Admin Blade compartilha **Actions**, nunca Controllers. Ver `ADR-0001`.
3. **Core independente de HTTP**: Actions recebem DTOs, retornam DTOs/void, emitem Events. Nunca tocam em `Illuminate\Http\*`. Garantido por Pest arch tests.
4. **Dual-mode Sanctum**: SPA (cookie) + token (mobile). Ver `ADR-0003`.
5. **IDs ULID públicos**: `CHAR(26)` em toda entidade exposta. BIGINT sequencial só no banco. Ver `ADR-0004`.
6. **Idempotência 3-camadas**: header `X-Idempotency-Key` + cache Redis 24h + unique constraint em DB. Ver `ADR-0005`.
7. **Concorrência de seating**: Redis lock por `assento_id` + `lockForUpdate` em `assentos` + unique parcial `WHERE status IN ('hold','confirmada')` em `reservas_assentos`. Ver `ADR-0006`.
8. **OpenAPI via Scramble** (`dedoc/scramble`): spec gerada automaticamente de FormRequests e Resources. Ver `ADR-0007`.
9. **Verbos em URL** para transições de state machine (`/reservas/{id}/confirmar`). Ver `ADR-0008`.
10. **Snapshots imutáveis JSONB** em `adesoes`, `convites`, `reservas_assentos` (confirmada), `pedidos_extras` (pago). Ver `ADR-0009`.
11. **Enums backed PHP 8.1+** em 100% dos campos finitos. Ver `ADR-0010`.
12. **Horizon + Redis** para filas segregadas (`default`, `notifications`, `webhooks`, `exports`, `critical-seating`). Ver `ADR-0011`.
13. **Spatie Permission com `guard_name`** explícito por modelo (`sanctum`, `admin`). Ver `ADR-0012`.
14. **Webhook HMAC + `firstOrCreate`** no `webhook_eventos` + processamento em job pós-commit. Ver `ADR-0013`.
15. **Valores monetários INTEGER centavos**. Ver `ADR-0014`.

### 4.2 Abordagens de qualidade

| Qualidade                    | Abordagem                                                                                                |
| ---------------------------- | -------------------------------------------------------------------------------------------------------- |
| **Correção em concorrência** | Defesa em profundidade: lock Redis + `lockForUpdate` + unique parcial DB + idempotency key.              |
| **Segurança**                | Guards isolados, Policies em todo recurso, rate limiting por contexto, HMAC em webhook, token hash only. |
| **Manutenibilidade**         | Bounded contexts, Actions finas, DTOs tipados, arch tests Pest, PHPStan level 6.                         |
| **Observabilidade**          | Logs JSON com `request_id` + `correlation_id`, Pulse para métricas internas, Sentry p/ erros.            |
| **Performance**              | `preventLazyLoading` em dev, eager loading explícito, cache com invalidação por evento.                  |
| **Testabilidade**            | Actions puras (sem Request), factories por Model, states nomeados, feature + arch tests.                 |

### 4.3 Organização técnica

Cada **bounded context** tem:

- Actions em `App\Actions\<Contexto>\*`
- DTOs em `App\Data\<Contexto>\*`
- Enums em `App\Enums\<Contexto>\*`
- Models em `App\Models\<Contexto>\*`
- Events em `App\Events\<Contexto>\*`
- Policies em `App\Policies\*` (namespace flat, mas 1 por recurso)
- Controllers API em `App\Http\Api\V1\Controllers\<Contexto>\*`
- FormRequests em `App\Http\Api\V1\Requests\<Contexto>\*`
- Resources em `App\Http\Api\V1\Resources\<Contexto>\*`
- Controllers Admin em `App\Http\Web\Admin\Controllers\<Area>\*`

Regras de namespace (prevenção de ciclos — §1.2 Planejamento):

- `Actions\*` → pode depender de `Data\*`, `Models\*`, `Services\*`, `Events\*`, `Enums\*`, `Exceptions\*`.
- `Actions\*` → **não pode** depender de `Http\*`, `Livewire\*`, `Jobs\*`.
- `Jobs\*` → depende apenas de `Actions\*` e `Services\*`.
- `Http\Api\V1\Controllers\*` → depende apenas de `Actions\*`, `Data\*`, `Http\*\Requests\*`, `Http\*\Resources\*`, `Policies\*`, `Enums\*`.
- `Models\*` → não importa `Actions\*`.

---

## 5. Blocos de construção (Building Block View)

### 5.1 Whitebox geral (C4 Level 2)

```mermaid
C4Container
title Container View — Portal ArtFinal v2

Person(client_web, "React Web SPA")
Person(client_mobile, "React Native App")
Person(guest, "Convidado (token mágico)")
Person(admin, "Admin/Comissão")

System_Boundary(s1, "Portal ArtFinal v2 — Monólito Laravel 13") {
    Container(nginx, "Nginx", "Reverse proxy + TLS", "Roteia /api/v1, /webhooks, /admin, /horizon, /pulse")
    Container(api_http, "API HTTP (PHP-FPM)", "Laravel 13", "Controllers finos → Actions. Sanctum (stateful + token). Scramble docs em /docs/api.")
    Container(admin_http, "Admin HTTP (PHP-FPM)", "Blade + Livewire 3", "Backoffice; compartilha Actions com API.")
    Container(webhook_http, "Webhook HTTP (PHP-FPM)", "Laravel 13", "HMAC validation + firstOrCreate em webhook_eventos → job.")
    Container(horizon, "Horizon Workers", "Supervisors Redis", "Filas: default, notifications, webhooks, exports, critical-seating.")
    Container(scheduler, "Scheduler", "php artisan schedule:run", "ExpirarHoldsJob (everyMinute), ReconciliarPagamentosJob (15min), AnonimizarDadosPosEventoJob (weekly).")

    ContainerDb(pg, "PostgreSQL 16", "Relational DB", "31 tabelas; JSONB para snapshots; unique parcial em reservas/adesões.")
    ContainerDb(redis, "Redis", "In-memory", "Cache, sessão, locks, filas, rate limiting.")
    Container(pulse, "Laravel Pulse", "Livewire", "Dashboard /pulse.")
}

System_Ext(itau, "Gateway Itaú")
System_Ext(s3, "S3 Privado")
System_Ext(sentry, "Sentry")
System_Ext(smtp, "Provedor SMTP")
System_Ext(expo, "Expo Push")

Rel(client_web, nginx, "HTTPS", "cookie laravel_session")
Rel(client_mobile, nginx, "HTTPS", "Authorization: Bearer")
Rel(guest, nginx, "HTTPS", "token mágico 64 chars")
Rel(admin, nginx, "HTTPS", "sessão admin")

Rel(nginx, api_http, "FastCGI")
Rel(nginx, admin_http, "FastCGI")
Rel(nginx, webhook_http, "FastCGI")

Rel(api_http, pg, "SQL/TLS")
Rel(api_http, redis, "RESP")
Rel(admin_http, pg, "SQL/TLS")
Rel(webhook_http, pg, "SQL/TLS")
Rel(webhook_http, redis, "dispatch job")
Rel(horizon, pg, "SQL/TLS")
Rel(horizon, redis, "RESP")
Rel(horizon, itau, "Saloon/HTTP", "consulta/reconciliação")
Rel(horizon, s3, "HTTPS", "upload exports/pdfs")
Rel(horizon, smtp, "SMTP")
Rel(horizon, expo, "HTTPS")

Rel(api_http, sentry, "HTTPS")
Rel(horizon, sentry, "HTTPS")
Rel(itau, webhook_http, "HTTPS", "POST /webhooks/pagamentos/itau")
```

### 5.2 Blocos por bounded context (C4 Level 3)

Cada bloco abaixo é um "container lógico" dentro do monólito. Fronteiras são namespaces, não processos separados.

```mermaid
flowchart LR
    subgraph BC1[Identidade e Acesso]
        AU[AdminUser]
        PU[PortalUser]
        CU[ComissaoUser]
        SP[Spatie Permission<br/>Roles/Permissions]
        CT[ConvidadoAccessToken]
    end

    subgraph BC2[Cadastro]
        ORG[Organizacao]
        INS[Instituicao]
        CUR[Curso]
        TUR[Turma]
        EVT[Evento]
        FOR[Formando]
    end

    subgraph BC3[Comercial / Adesão]
        PAC[Pacote]
        PRD[Produto]
        ADE[Adesao + AdesaoProduto]
        PAR[Parcela]
        PAG[Pagamento]
    end

    subgraph BC4[Convites]
        LOT[LoteConvite]
        CVT[Convite]
        RSV[RsvpHistorico]
        COT[CotaRegra]
    end

    subgraph BC5[Seating]
        MAP[MapaMesa]
        SET[Setor]
        MES[Mesa]
        ASS[Assento]
        RAS[ReservaAssento]
        RAH[ReservaHistorico]
    end

    subgraph BC6[Extras]
        PEX[ProdutoExtra]
        PED[PedidoExtra]
        PEI[PedidoExtraItem]
    end

    subgraph BC7[Engajamento / Enquetes]
        ENQ[Enquete]
        OPC[OpcaoEnquete]
        VOT[Voto]
    end

    subgraph BC8[Comunicação]
        TPL[TemplateNotificacao]
        NOT[Notificacao + Entrega]
    end

    subgraph BC9[Infra externa]
        WHE[WebhookEvento]
        ACL[ActivityLog]
    end

    BC1 --> BC2
    BC2 --> BC3
    BC3 -->|confirmada emite cota| BC4
    BC4 -->|RSVP confirmado habilita| BC5
    BC3 --> BC6
    BC6 -->|pago gera| BC4
    BC2 --> BC7
    BC3 -->|Pagamento| BC9
    BC6 -->|Pagamento| BC9
```

### 5.3 Whitebox: Seating (bounded context mais crítico)

```mermaid
flowchart TB
    subgraph HTTP[Camada HTTP api/v1]
        MC[MapaController]
        RC[ReservaController]
    end

    subgraph Actions[Actions Seating]
        RA[ReservarAssentoAction]
        CA[ConfirmarAssentoAction]
        LA[LiberarAssentoAction]
        EA[ExpirarHoldAssentoAction]
        TA[TrocarAssentoAction]
    end

    subgraph Services[Services]
        HS[HoldService]
        DS[DisponibilidadeService]
    end

    subgraph Models[Models]
        MM[MapaMesa]
        ME[Mesa]
        AS[Assento]
        RS[ReservaAssento]
    end

    subgraph Events[Events/Listeners]
        AR[AssentoReservado]
        AC[AssentoConfirmado]
        HE[HoldExpirado]
        IC[InvalidarCacheMapaAoReservar]
        PM[PublicarAtualizacaoMapaJob]
    end

    subgraph Infra[Infra]
        RD[(Redis lock + cache mapa)]
        PG[(PostgreSQL unique parcial)]
    end

    MC --> DS
    RC --> RA
    RC --> CA
    RC --> LA
    RC --> TA

    RA --> HS
    RA --> RD
    RA --> PG
    CA --> PG
    LA --> PG
    TA --> LA
    TA --> RA

    RA -.emite.-> AR
    CA -.emite.-> AC
    EA -.emite.-> HE
    AR --> IC
    HE --> IC
    IC --> PM
```

### 5.4 Whitebox: Pagamentos

```mermaid
flowchart TB
    subgraph HTTPApi[api/v1]
        PC[PagamentoController]
    end

    subgraph HTTPWebhook[webhook]
        WH[PagamentoWebhookController]
    end

    subgraph ActionsP[Actions]
        IP[IniciarPagamentoAction]
        PW[ProcessarWebhookPagamentoAction]
        CPE[ConfirmarPagamentoExtraAction]
        CA[ConfirmarAdesaoAction]
        ELC[EmitirLoteConvitesAction]
        EPE[EstornarPedidoExtraAction]
    end

    subgraph GW[Services/Gateway]
        PGC[PaymentGatewayContract]
        IG[ItauGateway]
        SG[StubGateway]
        ICN[ItauConnector Saloon]
    end

    subgraph Jobs[Jobs]
        PWJ[ProcessarWebhookPagamentoJob]
        RPJ[ReconciliarPagamentosJob]
    end

    subgraph DB[PostgreSQL]
        WE[webhook_eventos<br/>UNIQUE provider+gateway_reference]
        PGM[pagamentos]
        AD[adesoes]
        PE[pedidos_extras]
    end

    PC --> IP
    IP --> PGC
    PGC -.bind.-> IG
    PGC -.bind.-> SG
    IG --> ICN

    WH --> WE
    WE --> PWJ
    PWJ --> PW
    PW --> CPE
    PW --> CA
    PW --> EPE
    CPE --> ELC

    RPJ --> PGC
    RPJ --> PW
```

### 5.5 Whitebox: Convites + RSVP

```mermaid
flowchart TB
    subgraph HTTP[api/v1]
        CC[ConviteController]
        LCC[LoteConviteController]
        ACC[AcessoConviteController]
        RC[RsvpController]
    end

    subgraph MW[Middleware]
        RCT[ResolveConviteToken<br/>sha256]
    end

    subgraph Actions[Actions]
        EC[EmitirConviteAction]
        ELC[EmitirLoteConvitesAction]
        RSV[RegistrarRsvpAction]
        CNL[CancelarConviteAction]
        TRF[TransferirConviteAction]
    end

    subgraph Jobs[Jobs]
        ELJ[EmitirLoteConvitesJob<br/>chunks 500]
        ECE[EnviarConviteEmailJob]
        ERR[EnviarReminderRsvpJob]
    end

    subgraph Services[Services]
        CC_SVC[CotaCalculator]
    end

    LCC --> ELJ
    ELJ --> ELC
    ELC --> EC
    EC -.emite ConviteEmitido.-> ECE
    CC --> EC
    CC --> CNL
    CC --> TRF

    ACC --> RCT
    RC --> RCT
    RC --> RSV

    EC --> CC_SVC
```

---

## 6. Tempo de execução (Runtime View)

### 6.1 Reserva de assento (caminho feliz)

Referência: §5.1 do Planejamento. Decisão em `ADR-0006`.

```mermaid
sequenceDiagram
    autonumber
    participant C as Cliente (React/Mobile)
    participant N as Nginx
    participant MW as Middleware stack<br/>(api, auth:sanctum, idempotent, throttle)
    participant Ctl as ReservaController
    participant Pol as ReservaAssentoPolicy
    participant A as ReservarAssentoAction
    participant Lck as Redis lock<br/>seating:assento:{ulid}
    participant DB as PostgreSQL
    participant EB as Event Bus
    participant Q as Horizon (critical-seating)
    participant WS as Reverb/Push

    C->>N: POST /api/v1/eventos/{evt}/mesas/reservas<br/>X-Idempotency-Key: abc<br/>{assento_ulid, origem}
    N->>MW: FastCGI
    MW->>MW: valida Sanctum token/cookie
    MW->>MW: IdempotencyKeyGuard: cache[idem:user:route:abc] = sha256(payload)
    MW->>Ctl: request validado (ReservarAssentoRequest)
    Ctl->>Pol: authorize(reservar, evento)
    Pol-->>Ctl: true
    Ctl->>A: execute(ReservaRequestData)
    A->>DB: SELECT * FROM reservas_assentos WHERE idempotency_key='abc'
    alt já existe
        A-->>Ctl: ReservaResultData (estado atual)
    else não existe
        A->>Lck: lock("seating:assento:X", ttl=10s)<br/>block(3s)
        Lck-->>A: acquired
        A->>DB: BEGIN
        A->>DB: SELECT assento FOR UPDATE
        A->>DB: INSERT reserva (status=hold, hold_expires_at=now()+5min, idempotency_key='abc')
        Note over DB: UNIQUE PARCIAL reservas_assentos_ativa_por_assento<br/>garante 1 hold|confirmada por assento
        A->>DB: COMMIT
        A->>Lck: release
        A->>EB: AssentoReservado::dispatch(id)
        A-->>Ctl: ReservaResultData
    end
    Ctl-->>N: 201 Created + ReservaAssentoResource<br/>Location: /api/v1/eventos/{evt}/mesas/reservas/{reserva}
    N-->>C: 201
    EB->>Q: enqueue InvalidarCacheMapaAoReservar
    Q->>Q: Cache::tags(['evento:{id}','mapa'])->flush()
    Q->>WS: publica delta (mesa X, assento Y = hold)
```

### 6.2 Reserva de assento (caminhos de falha)

```mermaid
sequenceDiagram
    autonumber
    participant C as Cliente
    participant A as ReservarAssentoAction
    participant Lck as Redis lock
    participant DB as PostgreSQL

    alt Assento já reservado (concorrência)
        C->>A: execute()
        A->>Lck: lock(seating:assento:X)
        Lck-->>A: acquired
        A->>DB: SELECT FOR UPDATE
        A->>DB: checkDisponibilidade → falso
        A-->>C: AssentoIndisponivelException → 409
    else Hold expirou antes do confirmar
        C->>A: ConfirmarAssentoAction::execute()
        A->>DB: SELECT FOR UPDATE reserva
        A->>A: hold_expires_at.isPast() → throw
        A-->>C: HoldExpiradoException → 410 Gone
    else Idempotency key reutilizada com payload diferente
        C->>A: POST X-Idempotency-Key=abc (payload B)
        A-->>C: 409 (middleware bloqueia antes da Action)
    else Lock timeout (3s)
        C->>A: POST
        A->>Lck: block(3s)
        Lck-->>A: LockTimeoutException
        A-->>C: 409 (convertido no Handler)
    end
```

### 6.3 Webhook de pagamento idempotente

Referência: §5.5 do Planejamento. Decisão em `ADR-0013`.

```mermaid
sequenceDiagram
    autonumber
    participant IT as Gateway Itaú
    participant N as Nginx
    participant WC as PagamentoWebhookController
    participant GW as ItauGateway<br/>assinaturaValida()
    participant DB as PostgreSQL
    participant Q as Horizon (webhooks)
    participant J as ProcessarWebhookPagamentoJob
    participant PW as ProcessarWebhookPagamentoAction
    participant CPE as ConfirmarPagamentoExtraAction
    participant ELC as EmitirLoteConvitesAction

    IT->>N: POST /webhooks/pagamentos/itau<br/>X-Signature: hmac-sha256
    N->>WC: FastCGI
    WC->>GW: assinaturaValida(raw, sig)
    GW->>GW: hash_hmac('sha256', raw, secret)<br/>hash_equals(...)
    alt assinatura inválida
        GW-->>WC: false
        WC-->>IT: 401 {error:'invalid signature'}
    else válida
        GW-->>WC: true
        WC->>DB: firstOrCreate(webhook_eventos,<br/>{provider, gateway_reference})
        alt status='processado' (replay)
            WC-->>IT: 200 {status:'already_processed'}
        else novo evento
            WC->>Q: dispatch J(evento.id) afterCommit
            WC-->>IT: 202 {status:'accepted'}
        end
    end

    Q->>J: handle()
    J->>PW: execute(evento)
    alt tipo=pagamento.confirmado + pedido_extra
        PW->>CPE: execute(pedidoExtra)
        CPE->>DB: UPDATE pedidos_extras status=pago
        CPE->>ELC: execute(lote derivado)
        ELC->>DB: INSERT convites (chunks 500)
    else tipo=pagamento.estornado
        PW->>PW: chama EstornarPedidoExtraAction
    end
    J->>DB: UPDATE webhook_eventos status='processado', processado_at=now()
```

### 6.4 Emissão de lote de convites (assíncrono 202)

```mermaid
sequenceDiagram
    autonumber
    participant C as Cliente (formando)
    participant Ctl as LoteConviteController
    participant A as EmitirLoteConvitesAction
    participant DB as PostgreSQL
    participant Q as Horizon (default)
    participant J as EmitirLoteConvitesJob
    participant ECE as EnviarConviteEmailJob (notifications)

    C->>Ctl: POST /eventos/{evt}/convites/lotes<br/>X-Idempotency-Key: L-001<br/>{qtd: 120, template_id, ...}
    Ctl->>DB: INSERT lotes_convites (status=pendente, qtd=120)
    Ctl->>Q: dispatch J(lote.id)
    Ctl-->>C: 202 Accepted<br/>{lote_ulid, status_url: /eventos/{evt}/convites/lotes/{lote}}

    loop para cada chunk de 500
        Q->>J: handle()
        J->>A: execute(lote, chunk)
        A->>A: CotaCalculator.validarCotaDisponivel()
        A->>DB: BEGIN
        loop por convite
            A->>A: token = bin2hex(random_bytes(32))
            A->>A: token_hash = sha256(token)
            A->>DB: INSERT convite (status=emitido, token_hash, snapshot_regra)
            A->>A: ConviteEmitido::dispatch(convite.id)
        end
        A->>DB: COMMIT
    end
    A->>DB: UPDATE lote status='concluido'

    Note over ECE: Em paralelo, listener<br/>EnviarEmailConviteAoEmitir<br/>enfileira ECE por convite

    par email com token bruto
        ECE->>ECE: render template com link<br/>route('api.v1.convite.show', token_bruto)
        ECE->>ECE: SMTP envia
    end

    C->>Ctl: GET /eventos/{evt}/convites/lotes/{lote}
    Ctl-->>C: 200 {status:'concluido', qtd_emitida:120, qtd_falhou:0}
```

### 6.5 Login SPA vs Mobile

```mermaid
sequenceDiagram
    autonumber
    participant WEB as React Web SPA
    participant MOB as React Native App
    participant N as Nginx
    participant L as LoginController
    participant Au as Auth::guard('portal')
    participant DB as PostgreSQL

    rect rgb(230, 245, 255)
        Note over WEB,DB: Fluxo SPA (cookie)
        WEB->>N: GET /sanctum/csrf-cookie
        N-->>WEB: 204 + cookie XSRF-TOKEN
        WEB->>N: POST /api/v1/auth/login<br/>{email, password, mode:'spa'}<br/>X-XSRF-TOKEN
        N->>L: request
        L->>Au: attempt(credentials)
        Au->>DB: SELECT portal_users WHERE email=?
        DB-->>Au: user
        Au-->>L: ok
        L->>L: session->regenerate()
        L-->>WEB: 200 {user} + Set-Cookie laravel_session (HttpOnly, Secure, SameSite=lax)
        WEB->>N: GET /api/v1/me (cookie auto)
        N-->>WEB: 200 {user}
    end

    rect rgb(255, 245, 230)
        Note over MOB,DB: Fluxo Token (mobile)
        MOB->>N: POST /api/v1/auth/login<br/>{email, password, mode:'token', device_name:'iPhone de Maria'}
        N->>L: request
        L->>Au: attempt(credentials)
        Au-->>L: ok
        L->>DB: getAllPermissions() → abilities
        L->>DB: createToken(device_name, abilities)
        L-->>MOB: 200 {access_token, abilities, user}
        MOB->>MOB: storage seguro (Keychain/Keystore)
        MOB->>N: GET /api/v1/me<br/>Authorization: Bearer <token>
        N-->>MOB: 200 {user}
    end
```

### 6.6 Expiração de hold (scheduled)

```mermaid
sequenceDiagram
    autonumber
    participant S as Scheduler (everyMinute)
    participant Q as Horizon (critical-seating)
    participant J as ExpirarHoldsJob
    participant DB as PostgreSQL
    participant EB as Event Bus
    participant CL as Listener<br/>InvalidarCacheMapaAoReservar

    S->>Q: dispatch ExpirarHoldsJob<br/>(onOneServer, withoutOverlapping)
    Q->>J: handle()
    J->>DB: SELECT id FROM reservas_assentos<br/>WHERE status='hold' AND hold_expires_at < now()<br/>LIMIT 500
    DB-->>J: [ids]
    alt ids vazios
        J-->>Q: done
    else ids presentes
        J->>DB: BEGIN
        J->>DB: UPDATE ... SET status='expirada', hold_expires_at=null
        J->>DB: COMMIT
        loop por id
            J->>EB: HoldExpirado::dispatch(id)
            EB->>CL: handle
            CL->>CL: Cache::tags(['evento:{id}','mapa'])->flush()
        end
    end
```

---

## 7. Deployment

### 7.1 Visão de produção

```mermaid
flowchart TB
    subgraph Internet
        USR[Usuários<br/>React Web, Mobile]
        GW[Itaú / Expo / SES]
    end

    subgraph Edge[Edge / CDN]
        CF[CloudFront / CDN<br/>TLS terminação]
    end

    subgraph AppVPC[VPC — Aplicação]
        direction TB
        subgraph NginxTier[Nginx Tier]
            NX1[nginx pod 1]
            NX2[nginx pod 2]
        end
        subgraph AppTier[App Tier]
            PHP1[PHP-FPM pod 1<br/>api + admin + webhook]
            PHP2[PHP-FPM pod 2]
            PHP3[PHP-FPM pod 3]
        end
        subgraph WorkerTier[Worker Tier]
            HZ1[Horizon supervisor<br/>default + notifications]
            HZ2[Horizon supervisor<br/>webhooks]
            HZ3[Horizon supervisor<br/>exports]
            HZ4[Horizon supervisor<br/>critical-seating]
            SCH[Scheduler<br/>php artisan schedule:work]
        end
    end

    subgraph DataVPC[VPC — Dados]
        PG[(PostgreSQL 16<br/>primary + standby)]
        RD[(Redis<br/>cache + queue + session)]
    end

    subgraph Ext[Serviços externos]
        S3[S3 privado]
        SE[Sentry]
        PU[Pulse interno /pulse]
    end

    USR --> CF
    CF --> NX1
    CF --> NX2
    NX1 --> PHP1
    NX1 --> PHP2
    NX2 --> PHP2
    NX2 --> PHP3

    GW -->|webhooks| CF
    CF -->|/webhooks/*| NX1

    PHP1 --> PG
    PHP2 --> PG
    PHP3 --> PG
    PHP1 --> RD
    PHP2 --> RD
    PHP3 --> RD

    HZ1 --> RD
    HZ2 --> RD
    HZ3 --> RD
    HZ4 --> RD
    HZ1 --> PG
    HZ2 --> PG
    HZ3 --> PG
    HZ4 --> PG
    SCH --> RD

    HZ1 --> S3
    HZ3 --> S3

    PHP1 -.->|DSN| SE
    HZ1 -.->|DSN| SE
```

### 7.2 Ambientes

| Ambiente   | Infra                                       | Observações                                                   |
| ---------- | ------------------------------------------- | ------------------------------------------------------------- |
| `local`    | Laradock (docker-compose) no macOS          | PHP 8.4, PG 16, Redis, Mailpit, nginx. `.env.local`.          |
| `staging`  | 1× app, 1× worker, PG single-AZ             | Dados de seed + massa sintética. Deploy por branch `develop`. |
| `producao` | 2–3× app, 4× worker (por supervisor), PG HA | Deploy por tag `v*` após CI verde.                            |

### 7.3 Processo de deploy

1. `git push` → CI (GitHub Actions): Pint, PHPStan level 6, Pest (feature + arch + unit), Prettier.
2. Se verde e branch é `main` + tag: build image PHP-FPM 8.4 + Nginx.
3. `php artisan migrate --force` em pre-hook (baseline blue/green: só migrations backward-compatible).
4. Rolling update de PHP-FPM (2 pods mínimo).
5. Horizon: `php artisan horizon:terminate` → workers reciclam, novos pegam a nova imagem.
6. Scheduler: 1 pod `schedule:work`, ou cron externo chamando `schedule:run` a cada minuto.
7. Smoke test: `GET /up`, `GET /api/v1/auth/login` sem body → 422 esperado, `GET /horizon` autenticado.

### 7.4 Configuração sensível

- Secrets via Vault/SSM (`.env` apenas em dev).
- `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, `ITAU_WEBHOOK_SECRET`, `SENTRY_LARAVEL_DSN`, `AWS_*` injetados por variável de ambiente.

---

## 8. Conceitos transversais (Crosscutting)

### 8.1 Autenticação

4 guards (§6.1 Planejamento):

| Guard     | Driver  | Provider | Uso                                           |
| --------- | ------- | -------- | --------------------------------------------- |
| `admin`   | session | admins   | Backoffice Blade/Livewire.                    |
| `web`     | session | users    | Legado / fallback.                            |
| `sanctum` | sanctum | portals  | React Web (cookie) + Mobile (token).          |
| `convite` | custom  | —        | Resolve `Convite` por `sha256(token)` em URL. |

Spatie Permission com `guard_name` declarado por modelo. Ver `ADR-0012`.

### 8.2 Autorização

- **Policy por recurso** (§6.4 Planejamento): `EventoPolicy`, `AdesaoPolicy`, `ConvitePolicy`, `MapaMesaPolicy`, `ReservaAssentoPolicy`, `PedidoExtraPolicy`, `EnquetePolicy`, `RelatorioPolicy`.
- **Role `comissao`** nunca herda `admin`. Permissões explícitas `comissao.*` (§6.5).
- **Scope por evento**: policies checam `user->eventosAutorizados()->contains($evento->id)`.
- **FormRequest.authorize()** chama `$this->user()->can(...)` no topo para falhar rápido.

### 8.3 Cache

| Chave                            | TTL                   | Invalidação                                             |
| -------------------------------- | --------------------- | ------------------------------------------------------- |
| `evento:{ulid}:config`           | 30 min                | `EventoAtualizado`                                      |
| `evento:{id}:contadores:rsvp`    | 60 s                  | `RsvpRegistrado`, `ConviteEmitido`, `Cancelado`         |
| `evento:{id}:mapa:leitura`       | 5 min OU event-driven | `AssentoReservado`, `AssentoConfirmado`, `HoldExpirado` |
| `enquete:{id}:resultado:publico` | 1 min                 | `VotoRegistrado`, `EnqueteEncerrada`                    |
| `lookup:produtos:evento:{id}`    | 30 min                | Observer de `Produto`                                   |
| `permissions:user:{id}`          | 10 min                | `PermissaoAlterada` + Spatie cache                      |

**Nunca cachear:** disponibilidade final de assento em disputa; status financeiro pós-`PagamentoConfirmado`; dados sensíveis pessoais em cache compartilhado.

### 8.4 Rate limiting

`RateLimiterServiceProvider` define limiters nomeados (§2.10):

| Limiter   | Regra                                 | Escopo por      |
| --------- | ------------------------------------- | --------------- |
| `api`     | 120/min                               | `user_id` ou IP |
| `login`   | 5/min                                 | `email + IP`    |
| `convite` | 10/min                                | IP              |
| `seating` | 5/min                                 | `user_id` ou IP |
| `voto`    | 3/min                                 | `user_id` ou IP |
| `webhook` | 600/min (HMAC é a proteção principal) | IP              |

### 8.5 Logs

- Formato JSON (Monolog `JsonFormatter`) em `stderr`.
- Processor `CorrelationProcessor` injeta `request_id`, `actor_type`, `actor_id`, `evento_id`, `correlation_id` em todo record.
- Mascaramento (§11.8): tokens → `***`, CPF → `123.***.*89-00`, cartão nunca aparece.
- Nível: `info` para operações de negócio, `warning` para validação, `error` para exceção não tratada.

### 8.6 Snapshots

| Quando tirar                    | O que capturar                                                | Onde                               |
| ------------------------------- | ------------------------------------------------------------- | ---------------------------------- |
| `Adesao` → `ativa`              | preço, desconto, termo, condições, termo_hash                 | `adesoes.snapshot_comercial JSONB` |
| `Convite` → `emitido`           | regra de cota, template, lote                                 | `convites.snapshot_regra JSONB`    |
| `ReservaAssento` → `confirmada` | composição da mesa, setor, assento                            | histórico `reservas_historico`     |
| `PedidoExtra` → `pago`          | catálogo de produtos extras, preço, regra de emissão derivada | `pedidos_extras.snapshot_json`     |

**Propriedades:** imutáveis após confirmação. Nunca consultados por `WHERE`. `termo_hash = sha256(termo_html)` para prova em disputa.

### 8.7 Auditoria

`spatie/laravel-activitylog` com:

- Trait `LogsActivity` em entidades críticas.
- `activity_log` **append-only** — nunca DELETE/UPDATE (`ADR-0009`, §12.3).
- Retenção 2 anos, arquivo em S3 parquet depois.
- Campos adicionais: `request_id`, `actor_type`, `actor_id`, `correlation_id`.

### 8.8 i18n

- Projeto 100% PT-BR (CLAUDE.md §6).
- Validação via `laravellegends/pt-br-validator`.
- Enums têm método `label()` para renderização.
- Futuro: se expandir, adicionar `en` via `trans()` + `lang/en/*.php`.

### 8.9 Envelope de erro unificado

Referência: §2.11 Planejamento. Todo erro sob `api/*` e `webhooks/*` retorna:

```json
{
    "error": "ValidationError",
    "message": "Dados de entrada inválidos.",
    "details": { "fields": { "email": ["O campo email é obrigatório."] } },
    "request_id": "01J5K2N7QMHV1FJZ8H0PR3RV9C",
    "timestamp": "2026-04-17T14:32:11Z"
}
```

Mapeamento completo `Throwable → HTTP code` na §2.11. Handler global registrado em `bootstrap/app.php → ->withExceptions()`.

### 8.10 Concorrência (resumo)

| Operação              | Estratégia                                                                                           |
| --------------------- | ---------------------------------------------------------------------------------------------------- |
| Reserva de assento    | Redis lock + `lockForUpdate` em `assentos` + unique parcial em `reservas_assentos` + idempotency DB. |
| Confirmação de adesão | `DB::transaction` com UPDATE condicional `WHERE status='pendente_pagamento'`.                        |
| Webhook               | `firstOrCreate(webhook_eventos, {provider, gateway_reference})` + `dispatch afterCommit()`.          |
| Emissão em lote       | chunks 500 em `DB::transaction`; rollback parcial OK (idempotency key por convite).                  |
| Voto                  | `unique(enquete_id, ator_tipo, ator_id)` + `upsert` se `permite_edicao=true`.                        |

### 8.11 Observabilidade (consolidado)

- **Pulse** (`/pulse`): slow queries, cache miss ratio, exceptions, slow jobs, slow outgoing.
- **Horizon** (`/horizon`): filas, throughput, failed, waits.
- **Sentry**: 100% das exceções, performance 10%.
- **Alertas** (§12.3): webhook falha massiva, conflito seating, fila travada, 5xx > 1%, rate limit > 100/min.

---

## 9. Decisões arquiteturais (índice de ADRs)

Todos os ADRs seguem formato **MADR** e estão em `docs/architecture/adrs/`. Mudança significativa em qualquer área abaixo deve gerar novo ADR com `supersedes:` apontando para o anterior.

| ID       | Título                                                             | Status   |
| -------- | ------------------------------------------------------------------ | -------- |
| ADR-0001 | API-first + versionamento `api/v1`                                 | Accepted |
| ADR-0002 | Monólito modular vs microservices                                  | Accepted |
| ADR-0003 | Sanctum dual-mode (SPA stateful + token mobile)                    | Accepted |
| ADR-0004 | ULID público, BIGINT interno                                       | Accepted |
| ADR-0005 | Idempotência em 3 camadas (header + cache + DB unique)             | Accepted |
| ADR-0006 | Concorrência seating (Redis lock + unique parcial + lockForUpdate) | Accepted |
| ADR-0007 | OpenAPI via `dedoc/scramble`                                       | Accepted |
| ADR-0008 | Verbos em URL para state-machine transitions                       | Accepted |
| ADR-0009 | Snapshots imutáveis em JSONB                                       | Accepted |
| ADR-0010 | Enums PHP backed vs strings livres                                 | Accepted |
| ADR-0011 | Horizon + Redis para filas                                         | Accepted |
| ADR-0012 | Spatie Permission com `guard_name` por modelo                      | Accepted |
| ADR-0013 | Webhook HMAC + `firstOrCreate` + job pós-commit                    | Accepted |
| ADR-0014 | Valores monetários em INTEGER centavos                             | Accepted |

---

## 10. Requisitos de qualidade

Cenários no estilo **Bass–Clements–Kazman** (ISO 25010 adaptado).

### 10.1 Árvore de utilidade

```mermaid
flowchart LR
    U[Utility]
    U --> Per[Performance]
    U --> Rel[Confiabilidade]
    U --> Sec[Segurança]
    U --> Mnt[Manutenibilidade]
    U --> Scal[Escalabilidade]
    U --> Obs[Observabilidade]

    Per --> P1[P1: reserva ≤ 700ms p95]
    Per --> P2[P2: webhook ≤ 30s ponta-a-ponta]
    Per --> P3[P3: listagem 50 itens ≤ 200ms p95]

    Rel --> R1[R1: 0% double-booking]
    Rel --> R2[R2: replay webhook = 1 efeito]
    Rel --> R3[R3: 99,5% disponibilidade]

    Sec --> S1[S1: CPF/token nunca em log]
    Sec --> S2[S2: HMAC obrigatório em webhook]
    Sec --> S3[S3: policy em 100% recursos expostos]

    Mnt --> M1[M1: Action sem Illuminate\\Http]
    Mnt --> M2[M2: PHPStan level 6 verde]

    Scal --> Sc1[Sc1: 1000 reservas simultâneas]
    Scal --> Sc2[Sc2: 10k convites emitidos em 5min]

    Obs --> O1[O1: correlation_id cobre 5 entidades]
```

### 10.2 Cenários-chave

**Cenário P1 — Latência de reserva**

- **Fonte:** Cliente mobile autenticado.
- **Estímulo:** POST `/eventos/{evt}/mesas/reservas`.
- **Ambiente:** operação normal, 200 usuários ativos.
- **Artefato:** `ReservarAssentoAction`.
- **Resposta:** 201 + `ReservaAssentoResource`.
- **Medida:** p95 ≤ 700 ms; p99 ≤ 1 500 ms.

**Cenário R1 — Zero double-booking**

- **Fonte:** 1 000 clientes simultâneos.
- **Estímulo:** POST na mesma `/reservas` para o mesmo `assento_ulid`.
- **Ambiente:** produção simulada (load test).
- **Artefato:** `reservas_assentos` unique parcial + Redis lock.
- **Resposta:** 1 × 201, 999 × 409 `AssentoIndisponivel`.
- **Medida:** 0% de `status=hold|confirmada` duplicadas no banco.

**Cenário R2 — Idempotência de webhook**

- **Fonte:** Gateway Itaú (replay).
- **Estímulo:** mesmo `gateway_reference` enviado 10×.
- **Ambiente:** produção.
- **Artefato:** `webhook_eventos` unique + `ProcessarWebhookPagamentoJob`.
- **Resposta:** 1× 202 "accepted", 9× 200 "already_processed".
- **Medida:** `pedidos_extras.status='pago'` aplicado exatamente 1 vez.

**Cenário S1 — Vazamento de CPF**

- **Fonte:** Auditor LGPD lendo logs.
- **Estímulo:** buscar regex CPF em 30 dias de log.
- **Ambiente:** produção.
- **Artefato:** `CorrelationProcessor` + conveção de logging (§11.8).
- **Resposta:** CPF sempre mascarado (`123.***.*89-00`).
- **Medida:** 0 ocorrências de CPF completo em logs.

**Cenário M1 — Refatoração sem quebrar API**

- **Fonte:** Dev backend.
- **Estímulo:** mover uma Action de contexto.
- **Ambiente:** dev.
- **Artefato:** Pest arch tests + PHPStan.
- **Resposta:** CI bloqueia antes do merge se regra de namespace violada.
- **Medida:** 0 violações mergeáveis.

**Cenário Sc2 — Emissão em lote**

- **Fonte:** Formando representante.
- **Estímulo:** POST `/convites/lotes` com `qtd=10000`.
- **Ambiente:** produção.
- **Artefato:** `EmitirLoteConvitesJob` + Horizon.
- **Resposta:** 202 imediato; lote concluído em ≤ 5 min.
- **Medida:** throughput ≥ 33 convites/seg na fila `default`.

**Cenário O1 — Reconstrução de timeline**

- **Fonte:** Suporte investigando reclamação de formando.
- **Estímulo:** query por `correlation_id`.
- **Ambiente:** produção.
- **Artefato:** query UNION (§12.4 Planejamento) + colunas `correlation_id`.
- **Resposta:** timeline `convite → RSVP → reserva → pagamento`.
- **Medida:** ≤ 200 ms e cobre ≥ 5 entidades.

---

## 11. Riscos e dívida técnica

### 11.1 Matriz de riscos

| #   | Risco                                                               | Impacto | Probabilidade | Mitigação                                                                          |
| --- | ------------------------------------------------------------------- | ------- | ------------- | ---------------------------------------------------------------------------------- |
| R01 | Lógica de negócio migra para Blade/Livewire do admin                | Alta    | Média         | Arch tests Pest + PR review checklist + ADR-0001.                                  |
| R02 | Double-booking em seating sob concorrência real                     | Crítico | Baixa         | 3 camadas (lock + lockForUpdate + unique parcial) + testes de carga §10.3.         |
| R03 | Webhook duplicado aplica efeito 2×                                  | Alto    | Baixa         | `firstOrCreate` em `webhook_eventos` + job idempotente + ADR-0013.                 |
| R04 | Token de convite vaza em logs                                       | Alto    | Média         | Só `token_hash` persiste; middleware nunca loga `token`; arch test grep.           |
| R05 | N+1 em listagens de grande evento (10k convites)                    | Médio   | Alta          | `preventLazyLoading` em dev + eager explícito em Resources + Pulse slow query.     |
| R06 | Horizon trava fila `critical-seating` por memory leak               | Alto    | Baixa         | `memory=128`, `timeout=30`, `tries=1`; alerta Pulse + pager.                       |
| R07 | Permissão Spatie cache fica stale após mudança de role              | Médio   | Média         | `PermissaoAlterada` listener flusha Cache tag + ADR-0012.                          |
| R08 | PostgreSQL sem índice em `correlation_id` → query de timeline lenta | Médio   | Média         | Migration adiciona índice BTREE em 5 tabelas; alerta se > 200 ms.                  |
| R09 | Migrations incompatíveis com rolling deploy                         | Alto    | Média         | Regra: migrations aditivas; renomeações em 2 deploys (add col → move code → drop). |
| R10 | LGPD: retenção pós-evento ignorada                                  | Alto    | Média         | `AnonimizarDadosPosEventoJob` scheduled semanal + dashboard Pulse.                 |
| R11 | Coupling implícito entre bounded contexts via Model relations       | Médio   | Alta          | Regra: relation permitida mas regra de negócio cross-context vai via Event.        |
| R12 | Scramble gera spec desatualizada por FormRequest malformado         | Baixo   | Média         | CI roda `php artisan scramble:export` e falha se schema diverge.                   |

### 11.2 Dívida técnica conhecida (no início de F1)

| Item                                                                         | Severidade | Plano                                                               |
| ---------------------------------------------------------------------------- | ---------- | ------------------------------------------------------------------- |
| Guard `portal` legado (apenas session) coexistirá com `sanctum` até F3.      | Baixa      | F3: remover guard `portal` após migração 100% do Livewire.          |
| Admin em Livewire 3 duplica parte do fluxo da API (ex.: tabela de reservas). | Média      | Sempre consumir Actions; se vira gargalo, quebrar para API interna. |
| Falta ADR para escolha de biblioteca React (roteador, query client).         | Baixa      | Criar ADR-0015 quando F3 começar.                                   |
| Sem tracing distribuído real (OpenTelemetry).                                | Baixa      | F7: avaliar custo/benefício; `correlation_id` resolve 80%.          |

---

## 12. Glossário

| Termo                | Definição                                                                                                                |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| **Action**           | Classe invocável (`execute()`) que encapsula uma operação de domínio. Recebe DTO, retorna DTO. Não conhece HTTP.         |
| **Adesão**           | Contrato formal do formando com a empresa organizadora: pacote, parcelamento, termo aceito.                              |
| **Bounded context**  | Fronteira semântica de um subdomínio (ex.: Convites, Seating). Aqui = namespace e pasta.                                 |
| **Comissão**         | Formandos eleitos com privilégios parciais sobre sua turma. Role Spatie `comissao`.                                      |
| **Correlation ID**   | ID propagado entre request HTTP, job, webhook para reconstruir timeline funcional.                                       |
| **Cota**             | Regra declarativa que determina quantos convites um formando pode emitir.                                                |
| **DTO**              | Data Transfer Object. Aqui: `Spatie\LaravelData\Data`, readonly, com `toArray()`.                                        |
| **Evento (domínio)** | Uma formatura específica: data, local, turmas participantes, mapa de mesas, produtos extras.                             |
| **Evento (Laravel)** | `App\Events\*` disparado por Actions para desacoplar side-effects (notificação, cache flush).                            |
| **FormRequest**      | Classe Laravel que centraliza validação e autorização HTTP. Obrigatória em toda rota de input.                           |
| **Guard**            | Estratégia de autenticação do Laravel: `admin` (session), `web` (session), `sanctum` (token/cookie), `convite` (custom). |
| **HMAC**             | Hash-based Message Authentication Code. Validação de integridade + origem de webhook.                                    |
| **Hold**             | Estado transitório de reserva: 5 minutos para o formando confirmar antes do assento liberar.                             |
| **Idempotency key**  | Header `X-Idempotency-Key` + unique DB que garante que uma operação aconteça no máximo uma vez.                          |
| **JSONB**            | Tipo JSON binário do PostgreSQL. Usado para snapshots e configs.                                                         |
| **Lote de convites** | Agrupamento de convites emitidos juntos (ex.: 120 convites de uma família). Processamento async.                         |
| **MADR**             | Markdown Architectural Decision Records. Formato usado nos ADRs.                                                         |
| **Pacote**           | Oferta comercial (produto) que o formando contrata ao aderir.                                                            |
| **Portal**           | Faces do cliente (React Web + Mobile). Guard `sanctum`.                                                                  |
| **RSVP**             | _Répondez s'il vous plaît_ — confirmação/recusa de presença pelo convidado.                                              |
| **Sanctum stateful** | Modo SPA do Sanctum: cookie `laravel_session` + CSRF + `EnsureFrontendRequestsAreStateful`.                              |
| **Sanctum token**    | Modo mobile: `Authorization: Bearer` + `abilities`.                                                                      |
| **Scramble**         | `dedoc/scramble` — gera OpenAPI 3.x automaticamente de FormRequests/Resources.                                           |
| **Snapshot**         | Cópia imutável de dados comerciais/regulatórios no momento da confirmação de uma entidade transacional.                  |
| **State machine**    | Máquina de estados explícita em Enum backed (ex.: `StatusReserva`). Transições via endpoints com verbo.                  |
| **ULID**             | Identificador 128 bits lexicográficamente ordenado. 26 chars em base32. ID público.                                      |
| **Unique parcial**   | Índice UNIQUE + `WHERE` clause. Ex.: apenas UMA reserva ativa por assento.                                               |
| **Webhook event**    | Registro em `webhook_eventos` que cria idempotência dura por `(provider, gateway_reference)`.                            |

---

**Fim do SAD.** Revisões seguintes devem atualizar `version`, `date` e manter compatibilidade com o Planejamento Técnico como fonte-de-verdade. Novos ADRs são incluídos no índice da §9.
