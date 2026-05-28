---
title: Integrações Externas — API v1
version: 1.0.0
date: 2026-04-18
status: draft
---

# Integrações Externas — API v1

Contratos com sistemas externos que o backend consome ou expõe. Tudo que envolve rede externa (gateway financeiro, object storage, e-mail, push, observabilidade, WebSockets) vive aqui.

> Fonte: `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §8, §12 e referências cruzadas.

## 1. Gateway de Pagamento (Itaú)

### 1.1 Visão arquitetural

- **Contrato**: `App\Services\Gateway\Contracts\PaymentGatewayContract`.
- **Drivers**: `ItauGateway` (produção) e `StubGateway` (dev/testes).
- **Conector HTTP**: Saloon (`saloonphp/laravel-plugin`). Classes em `App\Services\Gateway\Saloon\Connectors\ItauConnector` e requests em `Saloon\Requests\{CriarCobranca,ConsultarCobranca}Request`.
- **Bind do driver**: `App\Providers\GatewayServiceProvider` lê `config('gateway.driver')`.

### 1.2 Contrato PHP

```php
interface PaymentGatewayContract
{
    public function criarCobranca(PagamentoIntentData $intent): string;         // retorna gateway_reference
    public function consultar(string $gatewayReference): WebhookPayloadData;
    public function assinaturaValida(string $rawPayload, string $signatureHeader): bool;
}
```

### 1.3 Endpoints consumidos (Itaú — referência do provedor)

> Nota: URLs e nomes finais seguem o manual do Itaú. Esta seção documenta o formato que o driver espera/produz.

| Método | URL (base: `config('gateway.itau.base_url')`) | Uso                                                    |
| ------ | --------------------------------------------- | ------------------------------------------------------ |
| `POST` | `/cobrancas`                                  | Cria cobrança (boleto, PIX, cartão).                   |
| `GET`  | `/cobrancas/{id}`                             | Consulta estado (usado em `ReconciliarPagamentosJob`). |
| `POST` | `/cobrancas/{id}/estornar`                    | Estorno (emitido por `EstornarPedidoExtraAction`).     |

### 1.4 Payload `CriarCobrancaRequest`

Derivado de `PagamentoIntentData`:

```json
{
    "valor_centavos": 150099,
    "moeda": "BRL",
    "descricao": "Adesão ArtFinal — Pacote Premium 01J...",
    "metodo": "boleto",
    "vencimento": "2026-05-15",
    "cliente": {
        "nome": "Mariana Souza",
        "cpf": "12345678900",
        "email": "mariana@usp.br"
    },
    "webhook_url": "https://api.portalartfinal.com.br/webhooks/pagamentos/itau",
    "metadata": {
        "adesao_ulid": "01J...",
        "correlation_id": "01J..."
    }
}
```

### 1.5 Resposta esperada

```json
{
    "cobranca": {
        "id": "ITAU-20260417-0001",
        "status": "pendente",
        "boleto": { "linha_digitavel": "...", "codigo_barras": "...", "pdf_url": "..." },
        "pix": { "qrcode": "...", "copia_e_cola": "..." }
    }
}
```

O driver mapeia para `PagamentoIntentData::gatewayReference`.

### 1.6 Webhook — payload recebido

```json
{
    "tipo": "pagamento.confirmado",
    "evento": { "id": "ITAU-EVT-20260417-0007" },
    "cobranca": { "id": "ITAU-20260417-0001", "status": "pago" },
    "valor_centavos": 150099,
    "pago_em": "2026-04-17T14:32:11-03:00"
}
```

Tipos esperados: `pagamento.confirmado`, `pagamento.autorizado`, `pagamento.falhou`, `pagamento.estornado`.

### 1.7 Segurança

- **HMAC SHA-256** em `X-Signature`, calculado sobre o raw body.
- `config('gateway.itau.webhook_secret')` — **não** versionado; em Vault.
- Comparação com `hash_equals` para evitar timing attack.
- IPs de origem do Itaú em allow-list no WAF/Nginx.

### 1.8 Variáveis de ambiente

```dotenv
GATEWAY_DRIVER=itau                 # ou 'stub'
GATEWAY_ITAU_BASE_URL=https://api.itau.com.br/cobrancas/v1
GATEWAY_ITAU_TOKEN=xxx
GATEWAY_ITAU_WEBHOOK_SECRET=xxx
```

### 1.9 Stub mode

Ativo quando `GATEWAY_DRIVER=stub` (default em `.env.example`).

- `criarCobranca` retorna `"STUB-" . Str::ulid()`.
- `consultar` retorna estado "pago" após 1 segundo (configurável).
- `assinaturaValida` retorna sempre `true`.

Útil para dev, testes Pest (`Feature\Gateway\StubGatewayTest`) e demo staging.

### 1.10 Resiliência

- **Timeout**: 5s por request (default Saloon).
- **Retry**: 2 tentativas com backoff `[2s, 5s]` em erros de rede (5xx transitórios). Não retentar em 4xx.
- **Circuit breaker**: opcional via `saloonphp/rate-limit-plugin` ou custom. Em falha persistente, lança `GatewayIndisponivelException` → 502 na API.
- **Reconciliação**: `ReconciliarPagamentosJob` a cada 15 min — lista pagamentos com `status IN ('pendente','autorizado')` há > 60 min e consulta o gateway. Divergência cria evento interno que passa por `ProcessarWebhookPagamentoAction`.

## 2. Object Storage (S3 Privado)

### 2.1 Propósito

Armazenar PDFs (termos consolidados, comprovantes), exports CSV/Excel e uploads de configurações. Nunca expor objeto público; leitura sempre via URL assinada.

### 2.2 Estrutura de buckets / prefixos

| Disk (Laravel) | Bucket / prefixo                                             | Conteúdo                   |
| -------------- | ------------------------------------------------------------ | -------------------------- |
| `s3-private`   | `portalartfinal-docs/`                                       | PDFs e XLSX gerados.       |
| `s3-private`   | `portalartfinal-docs/termos/<evento_ulid>/<adesao_ulid>.pdf` | Termos consolidados.       |
| `s3-private`   | `portalartfinal-docs/comprovantes/<pagamento_ulid>.pdf`      | Comprovantes de pagamento. |
| `s3-private`   | `portalartfinal-docs/exports/<YYYY-MM>/<job_ulid>.xlsx`      | Relatórios Excel.          |

### 2.3 Configuração

`config/filesystems.php`:

```php
's3-private' => [
    'driver'   => 's3',
    'key'      => env('AWS_ACCESS_KEY_ID'),
    'secret'   => env('AWS_SECRET_ACCESS_KEY'),
    'region'   => env('AWS_DEFAULT_REGION'),
    'bucket'   => env('AWS_BUCKET_DOCS'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'visibility' => 'private',
],
```

### 2.4 Upload privado

```php
Storage::disk('s3-private')->put($path, $content, [
    'visibility'  => 'private',
    'ContentType' => $mime,
    'Metadata'    => ['correlation_id' => $correlationId],
]);
```

### 2.5 URL assinada

```php
$url = Storage::disk('s3-private')->temporaryUrl($path, now()->addMinutes(5));
```

- **TTL máximo**: 5 minutos (enforced em `SignedUrlService`).
- Nunca commitar `AWS_*` em código.
- Nome do objeto: `Ulid::generate() . '.' . $ext` (nunca confiar no nome enviado pelo cliente).

### 2.6 Validação de uploads

- Tipo MIME: validar com `ext/fileinfo` (função `finfo_file`), **não** apenas extensão.
- Tamanho máximo: 10MB (configurável por tipo).
- Tipos aceitos (exemplos): `application/pdf`, `image/jpeg`, `image/png`.

### 2.7 Provedores compatíveis

- AWS S3.
- Cloudflare R2 (mesma interface; `AWS_ENDPOINT` + `AWS_USE_PATH_STYLE_ENDPOINT=true`).
- MinIO (dev local via Laradock; mesma interface).

## 3. E-mail

### 3.1 Drivers suportados

- **SMTP** — dev local (Mailpit em `:8125`).
- **SES** (AWS Simple Email Service) — produção recomendada.
- **Mailgun** — fallback.

### 3.2 Configuração

`.env`:

```dotenv
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=no-reply@portalartfinal.com.br
MAIL_FROM_NAME="ArtFinal"
```

### 3.3 Fila obrigatória

Todo e-mail é enfileirado: `Mail::to(...)->queue(...)` — nunca `Mail::send()` síncrono. Fila: `notifications` (prioridade normal).

### 3.4 Mailables mapeadas

| Classe                    | Evento gatilho                         | Template Blade                           |
| ------------------------- | -------------------------------------- | ---------------------------------------- |
| `ConvitePresencaMail`     | `ConviteEmitido`                       | `emails/convite/presenca.blade.php`      |
| `ReminderRsvpMail`        | `EnviarReminderRsvpJob`                | `emails/convite/reminder-rsvp.blade.php` |
| `PagamentoConfirmadoMail` | `PagamentoConfirmado`                  | `emails/pagamento/confirmado.blade.php`  |
| `AdesaoConfirmadaMail`    | `AdesaoConfirmada`                     | `emails/adesao/confirmada.blade.php`     |
| `ComprovanteExportMail`   | `GerarComprovantePagamentoJob` conclui | `emails/export/comprovante.blade.php`    |

### 3.5 Bounce handling

Callback de SES/Mailgun para `POST /webhooks/email/{provider}` (F7). Persistido em `notificacao_entregas` (append-only).

### 3.6 Anti-patterns

- ❌ `Mail::send()` síncrono.
- ❌ Dados sensíveis no corpo (CPF completo, senha).
- ❌ `Mail::raw()` — sempre usar Mailable com template.

## 4. Push (Expo) — F8

### 4.1 Propósito

Notificar usuários mobile (iOS/Android via Expo) sobre eventos críticos: convite recebido, pagamento confirmado, RSVP atualizado.

### 4.2 Arquitetura

- Token Expo registrado via `POST /api/v1/me/devices` com payload `{ "expo_push_token": "ExponentPushToken[...]", "plataforma": "ios|android" }`.
- Tabela `portal_user_devices (portal_user_id, expo_push_token UNIQUE, plataforma, last_seen_at)` — criada em F8.
- Envio via `PushService::send(Device $device, string $titulo, string $body, array $data)`.
- Request à Expo: `POST https://exp.host/--/api/v2/push/send` com `Authorization: Bearer <EXPO_ACCESS_TOKEN>`.

### 4.3 Job `NotificarPushJob`

- Fila: `notifications`, retry 3.
- Idempotente via `correlation_id`.
- Em falha permanente (Expo retorna `DeviceNotRegistered`), remove token da base.

## 5. Sentry (Error Tracking)

### 5.1 Setup

- Pacote: `sentry/sentry-laravel`.
- `SENTRY_LARAVEL_DSN` em `.env`.
- `SENTRY_TRACES_SAMPLE_RATE=0.1` (performance 10%).
- `SENTRY_SEND_DEFAULT_PII=false` (nunca enviar PII).

### 5.2 Scrubbing

- Campos sensíveis adicionados via `beforeSend`:
    - `cpf`, `cpf_cnpj`, `password`, `password_confirmation`, `token`, `secret`, `authorization`, `cookie`, `x-api-key`, `x-signature`.
- Request bodies acima de 10KB truncados.

### 5.3 Tags

Enriquecimento automático via middleware `AttachRequestId`:

```php
Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($request): void {
    $scope->setTag('request_id', $request->header('X-Request-Id'));
    $scope->setTag('correlation_id', $request->header('X-Correlation-Id'));
    if ($user = $request->user()) {
        $scope->setUser(['id' => $user->ulid, 'role' => $user->tipo ?? 'admin']);
    }
});
```

### 5.4 Alertas configurados

| Alerta                | Condição                                       | Canal             |
| --------------------- | ---------------------------------------------- | ----------------- |
| Webhook falha massiva | > 10 falhas em 5 min no mesmo provider         | Slack + Sentry    |
| Conflito de assento   | > 20 `AssentoIndisponivelException`/min        | Slack             |
| Fila travada          | `pending` em `critical-seating` > 50 por 2 min | Slack + PagerDuty |
| 5xx endpoint crítico  | taxa > 1% em 5 min                             | Pager             |
| Rate limit estourando | > 100 rate-limit responses/min                 | Slack             |

## 6. WebSockets / Reverb (F5 opcional)

### 6.1 Propósito

Empurrar delta do mapa de mesas em tempo real para clientes conectados. Alternativa: polling a cada 5s.

### 6.2 Setup

- Driver: Laravel Reverb (`php artisan reverb:install`).
- Canal privado por evento: `private-evento.{ulid}.seating`.
- Auth do canal via Sanctum (`routes/channels.php`).

### 6.3 Eventos broadcast

| Evento              | Payload                                             |
| ------------------- | --------------------------------------------------- | -------------- |
| `ReservaCriada`     | `{ assento_ulid, status: 'hold', hold_expires_at }` |
| `ReservaConfirmada` | `{ assento_ulid, status: 'confirmada' }`            |
| `ReservaCancelada`  | `{ assento_ulid, status: 'expirada'                 | 'cancelada' }` |

Disparo via Listener `InvalidarCacheMapaAoReservar` + `PublicarAtualizacaoMapaJob`.

### 6.4 Configuração

```dotenv
BROADCAST_DRIVER=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=reverb.portalartfinal.com.br
REVERB_PORT=443
REVERB_SCHEME=https
```

### 6.5 Fallback

Se Reverb offline, cliente React deve detectar via timeout de heartbeat e cair para polling a cada 5s sobre `GET .../mesas/mapa?since=<updated_at>`.

## 7. Horizon (Filas) — consumo interno

Não é integração "externa", mas é tratado aqui por ser infra de orquestração.

- **Dashboard**: `/horizon` (gate `auth:admin` + permission `admin.horizon.view`).
- **Filas MVP**: `default`, `notifications`, `webhooks`, `exports`, `critical-seating`.
- **Retry policy padrão**: 5 tentativas com backoff `[10, 30, 90, 300, 600]` segundos.
- **Dead-letter queue**: `failed_jobs` (nativa) + alerta Sentry.

## 8. Pulse (Observabilidade)

- Pacote: `laravel/pulse`.
- Dashboards custom (`/pulse`):
    - Slow queries.
    - Cache miss ratio.
    - Exceptions por endpoint.
    - Slow jobs.
    - Slow outgoing requests.
- Gate idêntico ao Horizon.

## 9. Tabela consolidada de variáveis de ambiente

| Chave                         | Módulo  | Default dev                    |
| ----------------------------- | ------- | ------------------------------ |
| `GATEWAY_DRIVER`              | Gateway | `stub`                         |
| `GATEWAY_ITAU_BASE_URL`       | Gateway | —                              |
| `GATEWAY_ITAU_TOKEN`          | Gateway | —                              |
| `GATEWAY_ITAU_WEBHOOK_SECRET` | Gateway | —                              |
| `AWS_ACCESS_KEY_ID`           | Storage | (MinIO local)                  |
| `AWS_SECRET_ACCESS_KEY`       | Storage | (MinIO local)                  |
| `AWS_DEFAULT_REGION`          | Storage | `us-east-1`                    |
| `AWS_BUCKET_DOCS`             | Storage | `portalartfinal-docs-dev`      |
| `AWS_ENDPOINT`                | Storage | `http://minio:9000`            |
| `AWS_USE_PATH_STYLE_ENDPOINT` | Storage | `true`                         |
| `MAIL_MAILER`                 | E-mail  | `smtp` (Mailpit)               |
| `MAIL_FROM_ADDRESS`           | E-mail  | `no-reply@portalartfinal.test` |
| `EXPO_ACCESS_TOKEN`           | Push    | — (só F8)                      |
| `SENTRY_LARAVEL_DSN`          | Sentry  | —                              |
| `SENTRY_TRACES_SAMPLE_RATE`   | Sentry  | `0.1`                          |
| `BROADCAST_DRIVER`            | Reverb  | `log` (sem broadcast em dev)   |
| `REVERB_*`                    | Reverb  | —                              |
| `SANCTUM_STATEFUL_DOMAINS`    | Auth    | `localhost:3000`               |
| `SESSION_DOMAIN`              | Auth    | `localhost`                    |

## 10. Matriz de dependências críticas

| Integração              | Impacto se indisponível         | Fallback                                                                      |
| ----------------------- | ------------------------------- | ----------------------------------------------------------------------------- |
| Gateway Itaú (produção) | Pagamentos bloqueados           | Banner de indisponibilidade; `ReconciliarPagamentosJob` retoma após recovery. |
| S3 privado              | PDFs/exports não gerados        | Job em fila `exports` repete com backoff; UI mostra "pronto em breve".        |
| E-mail (SES)            | Convites não saem               | Queue mantém; SES SLA 99.9% é aceitável; bounce rate monitorado.              |
| Expo Push               | Notificações mobile atrasam     | UI abre eventualmente com polling.                                            |
| Sentry                  | Observabilidade degradada       | Logs estruturados em stderr continuam.                                        |
| Reverb                  | Mapa não atualiza em tempo real | Cliente cai para polling 5s.                                                  |
| Horizon                 | Fila não processa               | Nenhum degrade silencioso — alerta `supervisor-*` parado.                     |

## 11. Anti-patterns proibidos

1. ❌ Chamar gateway externo direto do Controller/Livewire. **Sempre** via Action → Service → Saloon Connector.
2. ❌ Upload público para S3 (`visibility: public`). **Sempre** privado + URL assinada.
3. ❌ E-mail síncrono (`Mail::send`). **Sempre** queued.
4. ❌ Aplicar efeito de webhook fora de `firstOrCreate` em `webhook_eventos`.
5. ❌ Commitar chave de gateway em `.env.example` ou repositório.
6. ❌ Retornar URL S3 nativa (não assinada) em Resource.
7. ❌ Push Expo sem verificar `DeviceNotRegistered`.
8. ❌ Sentry com PII completo (`send_default_pii=true`).
