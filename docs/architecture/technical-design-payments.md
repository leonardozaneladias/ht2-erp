---
title: 'Technical Design — Pagamentos (intent → webhook → processar)'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# Technical Design — Pagamentos

**Status:** Accepted | **Data:** 2026-04-18 | **Contexto:** Cobrança, webhooks e reconciliação com Itaú (e outros) | **Tags:** pagamentos, saloon, webhook, idempotencia, driver

> Desenho técnico do bounded context `Pagamentos` (intent + recepção de webhook + processamento assíncrono + reconciliação). Referencia ADR-0013 (webhook HMAC), ADR-0005 (idempotência), ADR-0014 (centavos), ADR-0011 (Horizon) e §5.5, §5.6, §5.7, §8.1–§8.3 do PLANEJAMENTO_BACKEND_APIV1.md.

## 1. Objetivo e invariantes

### 1.1 Objetivo

Permitir que usuários autenticados iniciem uma intenção de pagamento (boleto, PIX, cartão via Itaú no MVP) e que o sistema aplique efeitos do pagamento (confirmação de adesão, confirmação de pedido extra) **no máximo uma vez**, de forma resiliente a replay, timeout e falhas de rede.

### 1.2 Invariantes duras

1. **Nenhum dado de cartão é armazenado** — apenas `gateway_reference` (§0 princípio 10).
2. **Webhook aplica efeito uma única vez** por `(provider, gateway_reference)` — unique composto DB.
3. **Valores monetários em `int` centavos** (ADR-0014) na persistência, DTO e payload de gateway.
4. **Processamento assíncrono** — webhook retorna `202` rapidamente; trabalho pesado é job na fila `webhooks`.
5. **Reconciliação periódica** — `ReconciliarPagamentosJob` a cada 15 min detecta divergências em pagamentos pendentes > 60 min.

## 2. Modelo de domínio

### 2.1 Entidades

- `Pagamento` (transacional): status, provider, gateway_reference, valor_centavos, modalidade.
- `Parcela` (referencia `Pagamento` quando confirmada).
- `WebhookEvento` (append-only): provider, gateway_reference UNIQUE composto, payload JSONB, status, recebido_at, processado_at, tentativas, ultimo_erro.

### 2.2 Enums (ADR-0010)

- `StatusPagamento`: `Pendente | Autorizado | Confirmado | Falhou | Estornado | Cancelado`
- `ModalidadePagamento`: `Boleto | Pix | Cartao`

## 3. Arquitetura — driver pattern

### 3.1 Contract

```php
interface PaymentGatewayContract
{
    public function criarCobranca(PagamentoIntentData $intent): string;       // retorna gateway_reference
    public function consultar(string $gatewayReference): WebhookPayloadData;  // reconciliação
    public function assinaturaValida(string $rawPayload, string $signature): bool;
}
```

### 3.2 Drivers e bind

```mermaid
graph LR
    subgraph "Provider layer"
        C[PaymentGatewayContract]
    end
    subgraph "Drivers concretos"
        I[ItauGateway]
        S[StubGateway]
    end
    subgraph "HTTP layer (Saloon)"
        Con[ItauConnector]
        R1[CriarCobrancaRequest]
        R2[ConsultarCobrancaRequest]
        Resp[CobrancaResponse]
    end
    C -.bind por env.-> I
    C -.fallback local/test.-> S
    I --> Con
    Con --> R1
    Con --> R2
    R1 --> Resp
    R2 --> Resp
```

`GatewayServiceProvider` faz o `bind` dinâmico baseado em `config('gateway.driver')`.

### 3.3 Saloon

- Connector: `ItauConnector(base_url, token)`.
- Requests: `CriarCobrancaRequest(PagamentoIntentData)`, `ConsultarCobrancaRequest($gatewayReference)`.
- Response: `CobrancaResponse` tipado + hidratação em `WebhookPayloadData`.

## 4. Fluxos — Mermaid

### 4.1 Iniciar pagamento (intent)

```mermaid
sequenceDiagram
    autonumber
    participant C as Cliente (SPA/RN)
    participant MW as Middleware (auth:sanctum + idempotent + throttle)
    participant Ctl as PagamentoController
    participant A as IniciarPagamentoAction
    participant GW as PaymentGatewayContract
    participant DB as PostgreSQL

    C->>MW: POST /api/v1/pagamentos/intents<br/>X-Idempotency-Key: abc
    MW->>Ctl: FormRequest validado
    Ctl->>A: execute(PagamentoIntentData)
    A->>DB: SELECT pagamento WHERE idempotency_key=abc
    alt já existe
        A-->>Ctl: PagamentoResultData (estado atual)
    else novo
        A->>DB: INSERT pagamento (status=pendente, valor_centavos, modalidade)
        A->>GW: criarCobranca(intent)
        GW-->>A: gateway_reference
        A->>DB: UPDATE pagamento SET gateway_reference, status=autorizado
        A-->>Ctl: PagamentoResultData (com boleto_url / pix_copia_e_cola / cartao_auth_url)
    end
    Ctl-->>C: 201 + PagamentoResource
```

### 4.2 Recepção de webhook (HMAC + firstOrCreate)

```mermaid
sequenceDiagram
    autonumber
    participant GW as Gateway (Itaú)
    participant WC as PagamentoWebhookController
    participant DB as PostgreSQL
    participant Q as Horizon (webhooks)

    GW->>WC: POST /webhooks/pagamentos/itau<br/>body + X-Signature
    WC->>WC: throttle:webhook (600/min por IP)
    WC->>WC: HMAC = hash_hmac(sha256, raw, secret)
    alt hash_equals(HMAC, X-Signature) == false
        WC-->>GW: 401 invalid signature
        Note over WC: log webhook.assinatura_invalida
    else assinatura ok
        WC->>DB: WebhookEvento::firstOrCreate(provider, gateway_reference, ...)
        alt já status=processado
            WC-->>GW: 200 { status: 'already_processed' }
        else novo ou pending
            WC->>Q: ProcessarWebhookPagamentoJob::dispatch(evento.id)
            WC-->>GW: 202 accepted
        end
    end
```

### 4.3 Processamento assíncrono (job)

```mermaid
sequenceDiagram
    autonumber
    participant Q as Horizon (webhooks)
    participant J as ProcessarWebhookPagamentoJob
    participant A as ProcessarWebhookPagamentoAction
    participant Conf as ConfirmarAdesaoAction
    participant Extra as ConfirmarPagamentoExtraAction
    participant Est as EstornarPedidoExtraAction
    participant DB as PostgreSQL
    participant Ev as Event Dispatcher

    Q->>J: handle(eventoId)
    J->>DB: SELECT WebhookEvento WHERE id=...
    J->>A: execute(webhookEvento)
    A->>A: decidir tipo (pagamento.confirmado / estornado / falhou)
    alt tipo = pagamento.confirmado de adesao
        A->>Conf: execute(adesao_ulid)
        Conf->>DB: UPDATE adesoes SET status=ativa, snapshot_comercial=...
        Conf->>Ev: AdesaoConfirmada::dispatch
    else tipo = pagamento.confirmado de pedido_extra
        A->>Extra: execute(pedido_ulid)
        Extra->>DB: UPDATE pedidos_extras SET status=pago
        Extra->>Ev: PedidoExtraPago::dispatch
        Note over Extra,Ev: Listener dispara EmitirLoteConvitesAction
    else tipo = pagamento.estornado
        A->>Est: execute(pedido_ulid)
        Est->>Ev: PedidoExtraEstornado::dispatch
    end
    A->>DB: UPDATE webhook_eventos SET status=processado, processado_at=now()
    J-->>Q: done
    alt job falha
        Note over Q: backoff [5s, 30s, 90s, 300s, 600s]<br/>até tries=5<br/>depois failed_jobs (DLQ)
    end
```

### 4.4 Reconciliação scheduled

```mermaid
sequenceDiagram
    autonumber
    participant S as Scheduler
    participant J as ReconciliarPagamentosJob
    participant DB as PostgreSQL
    participant GW as PaymentGatewayContract
    participant WC as "Mesmo path webhook"

    S->>J: a cada 15 min
    J->>DB: SELECT pagamentos WHERE status IN (pendente, autorizado) AND created_at < now()-60min
    loop cada pagamento
        J->>GW: consultar(gateway_reference)
        GW-->>J: WebhookPayloadData
        alt divergência
            J->>WC: submete payload como se fosse webhook<br/>(mesma ProcessarWebhookPagamentoAction)
            Note over J,WC: NUNCA aplica efeito diretamente.<br/>Re-usa pipeline do webhook para idempotência.
        end
    end
```

## 5. Segurança (§11.5, §11.6)

- **HMAC obrigatório** antes de qualquer persistência (ADR-0013).
- **Unique composto** `(provider, gateway_reference)` em `webhook_eventos`.
- **Replay protection temporal**: eventos com `recebido_at < now()-24h` descartados.
- **Nunca logar**: raw payload com PII, assinatura completa, gateway_reference em clear.
- **Secret** em vault + rotação auditada.
- **Webhook controller sem CSRF, sem auth:sanctum** — roda em `routes/webhook.php` isolado.

## 6. DTOs

```php
final readonly class PagamentoIntentData {
    public function __construct(
        public int $valorCentavos,             // ADR-0014
        public ModalidadePagamento $modalidade,
        public string $referencia,             // adesao_ulid | pedido_extra_ulid
        public string $atorUlid,
        public ?string $vencimento = null,
        public ?array $metadata = null,
    ) {}
}

final readonly class WebhookPayloadData {
    public function __construct(
        public string $gatewayReference,
        public string $eventoTipo,
        public int $valorCentavos,
        public string $statusGateway,
        public array $raw,
    ) {}
}
```

## 7. Observabilidade (§12)

- **Logs estruturados**: `webhook.recebido`, `webhook.assinatura_invalida`, `webhook.processado`, `webhook.falhou`.
- **Pulse**: dashboard de slow jobs em `webhooks`, taxa de falha por provider.
- **Alertas**:
    - `> 10 falhas de webhook em 5 min no mesmo provider` → Slack + Sentry.
    - `pending em webhooks > 20 por 2 min` → pager.
- **Tracing**: `correlation_id` propagado do intent até o webhook + job.

## 8. Testes críticos (§10.4)

1. **Webhook idempotente**: mesmo `gateway_reference` 10× → efeito aplicado 1×.
2. **Assinatura inválida**: retorna 401 e **não** persiste `WebhookEvento`.
3. **Processamento em job**: pedido extra pago 1× emite convites derivados em ≤ 30s (§F6 aceite).
4. **Reconciliação**: divergência injeta via pipeline do webhook, sem duplicar efeito.
5. **Retry exponencial**: job falha 4×, sucede no 5×, efeito aplicado 1×.

## 9. Rotas (resumo §2.2)

| Método | Rota                                  | Auth           | Idempotent |
| ------ | ------------------------------------- | -------------- | ---------- |
| POST   | `/api/v1/pagamentos/intents`          | sanctum        | sim        |
| GET    | `/api/v1/pagamentos/{pagamento:ulid}` | sanctum        | n/a        |
| POST   | `/webhooks/pagamentos/{provider}`     | HMAC signature | DB unique  |

## 10. Ligações

- ADR-0005, ADR-0011, ADR-0013, ADR-0014
- PLANEJAMENTO_BACKEND_APIV1.md §4.5, §4.6, §5.5, §5.6, §5.7, §7.3, §8.1, §8.2, §8.3, §10.4, §11.5, §11.6
- SAD arc42 seções "Integrações externas — Gateway" e "Runtime — Webhooks"
- technical-design-extras.md (consumidor: pedido extra pago → emite convites)
