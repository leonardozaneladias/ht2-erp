# SPEC-F-009 — Notas de Implementação

> Status: **implementado (fake)** — FakeGateway funcional, ItauGateway stub para Sprint 12.

## O que foi implementado

### Enums (`app/Enums/Pagamento/`)

- `StatusIntent` (Pendente, Aprovado, Rejeitado, Cancelado, Expirado) + `isTerminal()`
- `TipoEventoWebhook` (PagamentoAprovado, PagamentoCancelado, PagamentoEstornado, PagamentoFalhou)
- `StatusProcessamentoWebhook` (Pendente, Processando, Processado, Falha)

### DTOs readonly (`app/Data/Pagamento/`)

- `IntentPagamentoData` — entrada para criação de intent
- `IntentResultData` — retorno do gateway com referência, QR/boleto, expiração
- `StatusPagamentoData` — status consultado no gateway
- `WebhookValidadoData` — resultado da validação de assinatura HMAC

### Exceptions (`app/Exceptions/Pagamento/`)

- `GatewayUnavailable` (503), `PaymentRejected` (422), `InvalidWebhookSignature` (401)
- `GatewayNotImplemented` (501), `WebhookIdempotencyViolation` (200 OK silencioso)

### Interface + Manager + Drivers

- `PaymentGatewayContract` — 4 métodos: `criarIntent`, `consultar`, `cancelar`, `validarWebhook`
- `PaymentManager` extends `Illuminate\Support\Manager` — auto-resolve por nome (`createFakeDriver`, `createItauDriver`)
- `FakeGateway` — stateful via cache; `criarIntent` retorna DTO imediato e agenda `SimularWebhookJob`
- `ItauGateway` — stub que lança `GatewayNotImplemented` em todos os métodos

### Webhook pipeline

- Tabela `webhook_eventos` com UNIQUE (provider, gateway_reference, tipo_evento) e CHECK constraints
- `VerifyWebhookSignature` middleware — HMAC-SHA256 via `X-{Provider}-Signature`
- `PagamentoWebhookController` — `DB::transaction()` para idempotência via SAVEPOINT
- `ProcessarWebhookJob` — tries=4, backoff=[10,60,300], fila `gateway`
- `SimularWebhookJob` — dispara POST para o próprio webhook com delay (PIX=5s, Boleto=30s, Cartão=0s)

### Config e Provider

- `config/payment.php` — `default`, `gateways.fake`, `gateways.itau`
- `PaymentServiceProvider` — singleton `payment` + binding `PaymentGatewayContract`
- `.env PAYMENT_GATEWAY_DRIVER=fake` por padrão em dev/CI

### Rota

```
POST /webhooks/pagamentos/{provider}   (fake|itau)
middleware: throttle:webhook, payment.signature
```

## O que FALTA para Itaú real (Sprint 12)

1. **OAuth2 client credentials** para autenticação no Itaú (saloon connector)
2. **Resources Saloon** para PIX, Boleto, Cartão (endpoint por método)
3. **Mapeamento de erros** Itaú → exceptions internas
4. **SincronizarPagamentosPendentesJob** — reconciliação noturna via `consultar()`
5. **Testes de contrato** para Itaú — adicionar ao dataset em `PaymentGatewayContractTest`
6. **X-Itau-Signature** — verificar formato exato da assinatura Itaú (pode ser diferente de HMAC-SHA256 puro)

## Decisões arquiteturais

- **`DB::transaction()` no controller** (não no job): garante que constraint violation seja capturada como SAVEPOINT, sem abortar o contexto de transação externo (crítico para testes)
- **Cache para FakeGateway** (não DB): estado de intent do fake é volátil por design — não deve poluir `webhook_eventos`
- **Delay no SimularWebhookJob**: simula latência realista de rede do gateway; facilita teste de cenários com `Queue::fake() + travel()`
- **ItauGateway em arquivo próprio** (não anonymous class): permite adicionar implementação real sem refatorar o Manager
