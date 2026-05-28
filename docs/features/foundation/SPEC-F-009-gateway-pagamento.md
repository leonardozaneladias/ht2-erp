---
title: SPEC-F-009 — Gateway de Pagamento (infra)
version: 0.1.0
date: 2026-04-19
status: implementado (fake)
feature_id: SPEC-F-009
fase: foundation
story_points: 8
depends_on: []
unlocks: [SPEC-003, SPEC-007, SPEC-010]
---

# SPEC-F-009 — Gateway de Pagamento (infra)

> **Fundacional.** Consolida a camada técnica reutilizável de pagamentos: interface `PaymentGatewayContract`, drivers (Itaú, Stub), webhooks com HMAC, fila dedicada, retry/backoff. O conteúdo já existe no [`PLANEJAMENTO_BACKEND_APIV1.md` §8](../../prd/PLANEJAMENTO_BACKEND_APIV1.md), mas fragmentado; esta spec formaliza como referência única para SPEC-003 (portal), SPEC-007 (extras), SPEC-010 (adesão pública).

---

## 1. Escopo

### 1.1 Coberto

- Contrato da interface de gateway (`PaymentGatewayContract`)
- Drivers: `ItauGateway` (Saloon), `StubGateway` (desenvolvimento/testes)
- Webhook receiver com HMAC-SHA256
- Tabela `webhook_eventos` (audit trail)
- Fila `gateway` no Horizon com backoff `[10, 60, 300]`
- `GatewayServiceProvider` (binding + driver selection via config)

### 1.2 Fora do escopo

- UX de pagamento (SPEC-003)
- Cálculo de valor a pagar (SPEC-F-006)
- Reembolso pós-transação (ainda não decidido — avaliar em SPEC-003)

---

## 2. Interface principal

```php
interface PaymentGatewayContract
{
    public function criarIntent(IntentPagamentoData $data): IntentResultData;
    public function consultar(string $reference): StatusPagamentoData;
    public function cancelar(string $reference): bool;
    public function validarWebhook(Request $request): WebhookValidadoData;
}
```

---

## 3. Drivers

### 3.1 `ItauGateway`

- Via `saloonphp/laravel-plugin`
- Connector: `ItauConnector` com base URL e auth (OAuth2 client credentials)
- Resources: `CriarBoletoResource`, `CriarPixResource`, `CriarCartaoResource`, `ConsultarTransacaoResource`
- Segredos via `config/services.itau`: `client_id`, `client_secret`, `webhook_secret`
- Observability: logs no canal `gateway` (sem PAN/dados sensíveis)

### 3.2 `StubGateway`

- Simula respostas deterministicas para desenvolvimento
- Resolve por padrão método: PIX → aprovado em 5s; Boleto → aprovado em 30s; Cartão → aprovado imediatamente
- Controlável via `X-Stub-Scenario` header (ex: `failure`, `timeout`, `decline`)

---

## 4. Seleção do driver

```php
// config/services.php
'payment_gateway' => [
    'default' => env('PAYMENT_GATEWAY_DRIVER', 'stub'),
    'drivers' => [
        'itau' => ['class' => ItauGateway::class],
        'stub' => ['class' => StubGateway::class],
    ],
],
```

Feature flag por organização no futuro (SPEC posterior).

---

## 5. Webhooks

### 5.1 Recepção

`POST /webhook/itau` (em `routes/webhook.php`, sem CSRF) → middleware `VerifyWebhookSignature` valida HMAC-SHA256 com `X-Itau-Signature` header → salva em `webhook_eventos` → enfileira `ProcessarWebhookJob`.

### 5.2 `webhook_eventos` — tabela

| Campo                  | Tipo                                                          |
| ---------------------- | ------------------------------------------------------------- |
| `id`, `ulid`           |                                                               |
| `provider`             | VARCHAR(30) (ex: `itau`, `stub`)                              |
| `gateway_reference`    | VARCHAR(255)                                                  |
| `tipo_evento`          | VARCHAR(50) (ex: `pagamento_aprovado`, `pagamento_cancelado`) |
| `payload_bruto`        | JSONB                                                         |
| `assinatura_validada`  | BOOLEAN                                                       |
| `status_processamento` | enum: `pendente`, `processando`, `processado`, `falha`        |
| `tentativas`           | SMALLINT                                                      |
| `ultimo_erro`          | TEXT nullable                                                 |
| `processado_em`        | DATETIME nullable                                             |
| `timestamps`           |                                                               |

### 5.3 Idempotência

- UNIQUE (provider, gateway_reference, tipo_evento)
- Replay do mesmo webhook: retorna 200 OK sem reprocessar

---

## 6. Fila e retry

- Fila: `gateway` (alta prioridade no Horizon, config/horizon.php)
- Backoff: `[10, 60, 300]` segundos (3 tentativas)
- Após última falha: move para `failed_jobs` e dispara alerta Sentry
- `ProcessarWebhookJob` é idempotente (chaveado por webhook_eventos.id)

---

## 7. Pontos a expandir na versão `draft`

- [ ] DTOs completos (`IntentPagamentoData`, `IntentResultData`, `StatusPagamentoData`, `WebhookValidadoData`)
- [ ] Mapeamento de códigos de erro do Itaú → exceções de domínio
- [ ] Tokenização de cartão: `PAN` jamais trafega no backend; apenas token do provedor
- [ ] Reconciliação noturna: `SincronizarPagamentosPendentesJob` verifica status de intents criados >1h sem webhook
- [ ] Teste de contrato: subir mock HTTP do Itaú e validar resposta do `ItauGateway`
- [ ] Testes: webhook replay (idempotência), assinatura inválida, timeout no gateway, retry com backoff
- [ ] Integração com SPEC-F-011 Auditoria: todo `criarIntent` loga `causer + amount + method`

---

## 8. Referências

- [`docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §8](../../prd/PLANEJAMENTO_BACKEND_APIV1.md) — conteúdo já existente, consolidado aqui
- [`docs/modules/16-gateway-itau.md`](../../modules/16-gateway-itau.md) — placeholder a expandir
- [`SPEC-003`](../SPEC-003-financeiro-pagamento.md) — consumidor principal (portal)
- [`SPEC-007`](../SPEC-007-extras.md) — consumidor (pagamento de extras)
- [`SPEC-010`](../SPEC-010-adesao-publica-codigo-contrato.md) — consumidor (1ª parcela após commit)

---

_**Estado:** `stub`. Conteúdo técnico já existe em PLANEJAMENTO; esta spec é principalmente de **consolidação** em um ponto único._
