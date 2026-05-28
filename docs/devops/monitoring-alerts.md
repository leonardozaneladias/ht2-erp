---
title: Monitoramento e Alertas — Backend API v1
version: 1.0.0
date: 2026-04-18
status: draft
escopo: Backend API v1 — Portal ArtFinal
stack: Laravel Pulse · Horizon · Sentry · Monolog JSON · Grafana
publico: DevOps, SRE, Engenharia
---

# Monitoramento e Alertas — Portal ArtFinal Backend API v1

Documento que define a estratégia de observabilidade: logs estruturados, métricas, dashboards, alertas, tracing funcional e SLO/SLI.

Base normativa:

- [`PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) §12
- [`runbook-operations.md`](runbook-operations.md) — resposta aos alertas aqui definidos
- [`engineering-standards.md`](engineering-standards.md) — padrões de código

---

## Sumário

1. Logs estruturados
2. Pulse — cards custom
3. Horizon — dashboards
4. Alertas — tabela completa
5. Sentry — configuração
6. Tracing funcional
7. SLO / SLI

---

## 1. Logs estruturados

### 1.1 Formato JSON obrigatório

Todo log em produção é **JSON**, uma linha por evento, gravado em `stderr` para captura pelo daemon de logs (Fluent Bit → CloudWatch/S3).

Exemplo de record:

```json
{
    "timestamp": "2026-04-18T14:32:15.234Z",
    "level": "info",
    "message": "convite emitido",
    "channel": "api",
    "context": {
        "request_id": "01HZX2B3C4D5E6F7",
        "correlation_id": "01HZX2AA111BBB22",
        "actor_type": "portal_user",
        "actor_id": 12345,
        "actor_ulid": "01HZX1A9...",
        "evento_id": 42,
        "convite_ulid": "01HZX2B3CONV",
        "endpoint": "POST /api/v1/convites",
        "latency_ms": 67,
        "status": 201
    },
    "environment": "production",
    "release": "1.1.0+abc123"
}
```

### 1.2 `config/logging.php` — canal `api`

```php
<?php

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver'   => 'stack',
            'channels' => ['stderr'],
            'ignore_exceptions' => false,
        ],

        'stderr' => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'info'),
            'handler'   => StreamHandler::class,
            'formatter' => JsonFormatter::class,
            'formatter_with' => [
                'batchMode' => JsonFormatter::BATCH_MODE_NEWLINES,
            ],
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [
                \App\Logging\CorrelationProcessor::class,
                \App\Logging\SensitiveDataMasker::class,
            ],
        ],
    ],
];
```

### 1.3 `CorrelationProcessor`

Injeta em todo record: `request_id`, `correlation_id`, `actor_type`, `actor_id`, `release`, `environment`.

```php
<?php

declare(strict_types=1);

namespace App\Logging;

use App\Support\CorrelationContext;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class CorrelationProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context + [
            'request_id'     => CorrelationContext::requestId(),
            'correlation_id' => CorrelationContext::correlationId(),
            'actor_type'     => CorrelationContext::actorType(),
            'actor_id'       => CorrelationContext::actorId(),
            'release'        => config('app.release', 'unknown'),
            'environment'    => config('app.env'),
        ];

        return $record->with(context: $context);
    }
}
```

### 1.4 `SensitiveDataMasker`

Mascara campos sensíveis antes de gravar:

```php
<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class SensitiveDataMasker implements ProcessorInterface
{
    private const BLOCKLIST = ['password', 'senha', 'token', 'access_token', 'cartao_numero', 'cvv'];
    private const CPF_PATTERN  = '/\b(\d{3})\d{6}(\d{2})\b/';
    private const EMAIL_PATTERN = '/\b([a-zA-Z0-9._-]+)@([a-zA-Z0-9.-]+)\b/';

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $this->scrub($record->context);
        return $record->with(context: $context);
    }

    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::BLOCKLIST, true)) {
                $data[$key] = '***';
                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->scrub($value);
                continue;
            }
            if (is_string($value)) {
                $value = preg_replace(self::CPF_PATTERN, '$1.***.***-$2', $value);
                $value = preg_replace(self::EMAIL_PATTERN, '$1@***', $value);
                $data[$key] = $value;
            }
        }
        return $data;
    }
}
```

### 1.5 Campos obrigatórios por evento

| Evento             | Campos extras obrigatórios                       |
| ------------------ | ------------------------------------------------ |
| Request HTTP       | `endpoint`, `method`, `status`, `latency_ms`     |
| Action executada   | `action_class`, `result`, `duration_ms`          |
| Reserva de assento | `assento_ulid`, `reserva_ulid`, `origem`         |
| Convite emitido    | `convite_ulid`, `formando_id`, `lote_numero`     |
| Webhook recebido   | `provider`, `gateway_reference`, `assinatura_ok` |
| Job executado      | `job_class`, `queue`, `attempts`, `duration_ms`  |
| Cache hit/miss     | `cache_key`, `hit` (bool)                        |

### 1.6 Middleware `AttachRequestId`

Gera `request_id` no início da request e propaga em todo log do ciclo:

```php
public function handle(Request $request, Closure $next): Response
{
    $rid = $request->header('X-Request-Id') ?: (string) \App\Support\Ulid::generate();
    CorrelationContext::setRequestId($rid);

    $cid = $request->header('X-Correlation-Id') ?: $rid;
    CorrelationContext::setCorrelationId($cid);

    $response = $next($request);
    $response->headers->set('X-Request-Id', $rid);

    return $response;
}
```

---

## 2. Pulse — cards custom

### 2.1 Cards incluídos no dashboard

Em `resources/views/vendor/pulse/dashboard.blade.php`:

```blade
<x-pulse>
    <livewire:pulse.servers cols="full" rows="1" />
    <livewire:pulse.usage cols="4" rows="2" />
    <livewire:pulse.queues cols="4" rows="2" />
    <livewire:pulse.slow-queries cols="8" rows="2" />
    <livewire:pulse.slow-requests cols="8" rows="2" />
    <livewire:pulse.slow-jobs cols="4" rows="2" />
    <livewire:pulse.exceptions cols="4" rows="2" />
    <livewire:pulse.cache cols="4" rows="2" />

    {{-- Cards custom --}}
    <livewire:pulse.reservas-por-minuto cols="4" rows="2" />
    <livewire:pulse.idempotency-hit-rate cols="4" rows="2" />
    <livewire:pulse.conflito-assento cols="4" rows="2" />
    <livewire:pulse.webhook-processamento cols="4" rows="2" />
</x-pulse>
```

### 2.2 Card: Reservas por minuto

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Pulse;

use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
final class ReservasPorMinuto extends Card
{
    public function render()
    {
        $reservas = cache()->remember('pulse:reservas_por_min', 30, fn () =>
            \App\Models\Seating\ReservaAssento::query()
                ->where('created_at', '>', now()->subHour())
                ->selectRaw('date_trunc(\'minute\', created_at) AS minuto, COUNT(*) AS qtd')
                ->groupBy('minuto')
                ->orderBy('minuto')
                ->get()
                ->map(fn ($row) => ['t' => $row->minuto, 'v' => $row->qtd])
        );

        return view('livewire.pulse.reservas-por-minuto', [
            'reservas' => $reservas,
            'atual'    => $reservas->last()['v'] ?? 0,
        ]);
    }
}
```

Registrado via `Pulse::record('reserva_criada', 1)` dentro da `ReservarAssentoAction`.

### 2.3 Card: Idempotency hit rate

```php
Pulse::record('idempotency', $hit ? 'hit' : 'miss')->count();
```

Card lê as duas séries e calcula `hit / (hit + miss)`.

### 2.4 Card: Conflito de assento

Emite `Pulse::record('seating_conflict', 1)` no catch de `AssentoIndisponivelException`.

### 2.5 Card: Webhook processamento

Série dupla: `webhook_received` vs `webhook_processed`. Gap = fila atrasada.

### 2.6 Gate de acesso

`app/Providers/PulseServiceProvider.php`:

```php
Gate::define('viewPulse', function (\App\Models\Acesso\AdminUser $user) {
    return $user->hasPermissionTo('admin.observability.view');
});
```

---

## 3. Horizon — dashboards

### 3.1 Gate

```php
// App\Providers\AuthServiceProvider
Gate::define('viewHorizon', fn (\App\Models\Acesso\AdminUser $user) =>
    $user->hasPermissionTo('admin.horizon.view')
);
```

### 3.2 Supervisores monitorados

| Supervisor          | Fila(s)                    | maxProcesses prod | Alerta se                   |
| ------------------- | -------------------------- | ----------------- | --------------------------- |
| supervisor-default  | `default`, `notifications` | 20                | pending > 200 por 5 min     |
| supervisor-webhooks | `webhooks`                 | 6                 | pending > 20 por 2 min      |
| supervisor-exports  | `exports`                  | 2                 | pending > 5 por 30 min      |
| supervisor-seating  | `critical-seating`         | 4                 | pending > 50 por 2 min (P1) |

### 3.3 Tags de jobs

```php
public function tags(): array
{
    return ['evento:' . $this->eventoId, 'adesao:' . $this->adesaoUlid];
}
```

Permite filtrar jobs por evento no Horizon UI.

### 3.4 `horizon:snapshot`

Scheduled a cada 5 min — persiste métricas em Redis para histórico no dashboard.

### 3.5 LongWaitDetected

`config/horizon.php` → `waits`:

```php
'waits' => [
    'redis:default'          => 60,
    'redis:notifications'    => 60,
    'redis:webhooks'         => 30,
    'redis:exports'          => 600,
    'redis:critical-seating' => 10, // mais agressivo
],
```

Horizon dispara `LongWaitDetected` event. Listener `NotifyLongWaitListener` envia alerta Slack (ver §4).

---

## 4. Alertas — tabela completa

### 4.1 Matriz de alertas

| ID  | Alerta                                | Condição                                                          | Janela | Canal                            | Severidade | Runbook                                                                               |
| --- | ------------------------------------- | ----------------------------------------------------------------- | ------ | -------------------------------- | ---------- | ------------------------------------------------------------------------------------- |
| A01 | Webhook falha massiva                 | > 10 `failed_jobs` (`ProcessarWebhookPagamentoJob`) em 5 min      | 5 min  | Slack `#alerts-backend` + Sentry | P1         | [`runbook-operations.md §3.1`](runbook-operations.md#31-alerta-webhook-falha-massiva) |
| A02 | Conflito de assento massivo           | > 20 `AssentoIndisponivelException`/min                           | 1 min  | Slack `#alerts-backend`          | P2         | [`§3.2`](runbook-operations.md#32-alerta-conflito-de-assento)                         |
| A03 | Fila `critical-seating` travada       | `pending > 50` por 2 min contínuos                                | 2 min  | Slack + PagerDuty                | P1         | [`§3.3`](runbook-operations.md#33-alerta-fila-travada-critical-seating)               |
| A04 | 5xx endpoint `/api/v1/*`              | taxa > 1% em 5 min                                                | 5 min  | PagerDuty + Slack                | P1         | [`§3.4`](runbook-operations.md#34-alerta-5xx-endpoint-crítico)                        |
| A05 | Rate limit estourando                 | > 100 responses 429 por minuto                                    | 1 min  | Slack                            | P2         | [`§3.5`](runbook-operations.md#35-alerta-rate-limit-estourando)                       |
| A06 | Slow query PG                         | query > 1000ms (via Pulse)                                        | —      | Slack (baixa prio)               | P3         | [`§3.6`](runbook-operations.md#36-alerta-slow-queries-pg)                             |
| A07 | Redis OOM                             | memory usage > 90%                                                | 1 min  | PagerDuty + Slack                | P1         | [`§3.7`](runbook-operations.md#37-alerta-redis-oom)                                   |
| A08 | Horizon supervisor down               | qualquer supervisor em `stopped` ou `paused` > 5 min sem intenção | 5 min  | Slack                            | P1         | runbook-operations.md §3.3                                                            |
| A09 | Disk usage                            | qualquer volume com free < 20%                                    | 5 min  | Slack                            | P2         | Provisionar mais disco                                                                |
| A10 | Backup PG falhou                      | cron `backup-pg.sh` exit-code != 0                                | —      | Slack + email DBA                | P1         | runbook-operations.md §6                                                              |
| A11 | Sentry — release com crash-free < 99% | crash-free sessions < 99% na release atual                        | 15 min | Slack + PagerDuty                | P1         | runbook-deploy.md §7 (rollback)                                                       |
| A12 | `ExpirarHoldsJob` atrasado            | última execução > 3 min                                           | 3 min  | PagerDuty                        | P1         | runbook-operations.md §3.3                                                            |
| A13 | Sanctum tokens anômalos               | > 500 logins por minuto (possível brute force)                    | 1 min  | Slack + security                 | P2         | security-operations.md §7                                                             |
| A14 | Idempotency miss excessiva            | miss rate > 50% em janela de 10 min                               | 10 min | Slack                            | P3         | Investigar cliente gerando keys novas                                                 |

### 4.2 Configuração dos alertas

**Sentry (A04, A11):** configurado em Sentry UI → Alerts:

```
Name: API 5xx rate > 1%
Environment: production
Conditions: event count > 1% of total events, 5 min window
Actions: Slack #alerts-backend, PagerDuty escalation "backend-primary"
```

**Horizon (A01, A03, A08, A12):** implementados via `LongWaitDetected` event + custom listeners.

```php
// app/Providers/HorizonServiceProvider.php
public function boot(): void
{
    parent::boot();

    Horizon::routeSlackNotificationsTo(
        config('services.slack.webhook'),
        '#alerts-backend'
    );

    Horizon::night();
}
```

**Pulse (A02, A05, A14):** custom recorder + alerta via scheduled job checando agregados.

**Grafana/CloudWatch (A07, A09):** alertas de infra via CloudWatch → SNS → Slack/PagerDuty.

**Slack webhooks:** configurados em `.env` prod:

```dotenv
SLACK_WEBHOOK_ALERTS_BACKEND=https://hooks.slack.com/services/XXX/YYY/ZZZ
SLACK_WEBHOOK_DEPLOY=https://hooks.slack.com/services/XXX/AAA/BBB
PAGERDUTY_INTEGRATION_KEY=xxxxxxxxxxxx
```

---

## 5. Sentry — configuração

### 5.1 Instalação

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=https://xxx@sentry.io/123
```

### 5.2 `config/sentry.php`

```php
<?php

declare(strict_types=1);

return [
    'dsn'         => env('SENTRY_LARAVEL_DSN'),
    'release'     => env('APP_RELEASE'),
    'environment' => env('APP_ENV'),

    'sample_rate'         => env('SENTRY_SAMPLE_RATE', 1.0), // 100% de erros
    'traces_sample_rate'  => env('SENTRY_TRACES_SAMPLE_RATE', 0.1), // 10% perf prod

    'send_default_pii' => false,

    'breadcrumbs' => [
        'logs'         => true,
        'sql_queries'  => true,
        'sql_bindings' => false, // NUNCA true em prod — vaza dado
        'queue_info'   => true,
    ],
];
```

### 5.3 Release tracking

O CI cria a release no Sentry a cada deploy (ver [`ci-cd.md §3.3`](ci-cd.md)). Isso permite:

- Filtrar issues por release.
- `crash-free sessions` por release.
- Rollback guiado por métricas.

### 5.4 Filtros de ruído

Exceções que não vão para Sentry (muito ruído operacional, já tratadas):

```php
// app/Exceptions/Handler.php via withExceptions()
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->dontReport([
        \App\Exceptions\Seating\AssentoIndisponivelException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Validation\ValidationException::class,
    ]);
})
```

Essas exceções são contabilizadas em métricas custom do Pulse, não em Sentry.

### 5.5 Fingerprint custom

Para agrupar melhor:

```php
\Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($exception) {
    if ($exception instanceof \App\Exceptions\Pagamento\WebhookInvalidoException) {
        $scope->setFingerprint(['webhook-invalido', $exception->provider]);
    }
});
```

---

## 6. Tracing funcional

### 6.1 Coluna `correlation_id`

Tabelas com `correlation_id UUID/ULID`:

- `convites`
- `rsvp_historico`
- `reservas_assentos`
- `pedidos_extras`
- `pagamentos`
- `webhook_eventos`
- `notificacoes_entregas`

### 6.2 Propagação

- Primeiro contato externo (webhook recebido, convite acessado via token) **gera** um `correlation_id`.
- Request interno cliente → API pode trazer header `X-Correlation-Id`. Se vier, reutiliza; senão, gera.
- Toda action que persiste em tabela com `correlation_id` recebe o valor do `CorrelationContext` e grava.

### 6.3 Query de reconstrução

Reconstruir toda a jornada de um evento:

```sql
SELECT 'convite' AS tipo, codigo AS ref, created_at
  FROM convites WHERE correlation_id = :c
UNION ALL
SELECT 'rsvp', convite_id::text, created_at
  FROM rsvp_historico WHERE correlation_id = :c
UNION ALL
SELECT 'reserva', ulid, created_at
  FROM reservas_assentos WHERE correlation_id = :c
UNION ALL
SELECT 'pedido_extra', ulid, created_at
  FROM pedidos_extras WHERE correlation_id = :c
UNION ALL
SELECT 'pagamento', ulid, created_at
  FROM pagamentos WHERE correlation_id = :c
UNION ALL
SELECT 'webhook', gateway_reference, recebido_at
  FROM webhook_eventos WHERE correlation_id = :c
ORDER BY created_at;
```

### 6.4 Integração com logs

Todo log carrega `correlation_id` via `CorrelationProcessor`. Para investigar incidente:

```bash
# Buscar todos os logs de uma jornada
grep '"correlation_id":"01HZX2AA111BBB22"' /var/log/portalartfinal/*.log | \
    jq -r '[.timestamp, .level, .message, .context.endpoint] | @tsv'
```

### 6.5 Headers de resposta

Toda response expõe:

```
X-Request-Id: 01HZX2B3C4D5E6F7
X-Correlation-Id: 01HZX2AA111BBB22
```

Permite que o cliente inclua esses valores em bug report.

---

## 7. SLO / SLI

### 7.1 Tabela de SLOs

| SLI                                  | Objetivo | Janela mensal  | Error budget / mês |
| ------------------------------------ | -------- | -------------- | ------------------ |
| Uptime `/api/v1/*`                   | 99,5%    | calendar month | 3h 36min           |
| Latência p95 `/api/v1/*`             | ≤ 500ms  | 30 dias        | —                  |
| Latência p95 `POST /api/v1/reservas` | ≤ 700ms  | 30 dias        | —                  |
| Taxa de 5xx `/api/v1/*`              | ≤ 0,1%   | 30 dias        | —                  |
| Taxa de sucesso de pagamento         | ≥ 98%    | 30 dias        | —                  |
| `ExpirarHoldsJob` atraso p95         | ≤ 60s    | —              | —                  |
| MTTR (Mean Time To Recovery) P0/P1   | < 1h     | —              | —                  |
| Change failure rate                  | ≤ 10%    | 30 dias        | —                  |
| Backup diário PG completo            | 100%     | 30 dias        | 0 falhas           |

### 7.2 Cálculo de error budget

Uptime 99,5% em mês de 30 dias:

```
total_minutos = 30 * 24 * 60 = 43.200 min
permitido     = 43.200 * 0,005 = 216 min = 3h 36min
```

Se error budget consumir 80% (172 min downtime), o time **para o deploy de features** e foca em estabilidade até recuperar.

### 7.3 Como coletamos cada SLI

| SLI                      | Fonte                                                  |
| ------------------------ | ------------------------------------------------------ |
| Uptime                   | Healthcheck externo (UptimeRobot ou CloudWatch canary) |
| Latência p95             | Pulse slow-requests + CloudWatch metrics               |
| Taxa 5xx                 | Sentry aggregate + CloudWatch logs                     |
| Taxa sucesso pagamento   | `pagamentos.status = 'pago' / total` (SQL agregado)    |
| `ExpirarHoldsJob` atraso | Horizon runtime metric                                 |
| MTTR                     | Incident log manual                                    |
| Change failure rate      | Releases com rollback / total releases                 |
| Backup                   | Cron exit-code + S3 object existence                   |

### 7.4 Dashboard SLO

Grafana dashboard `portalartfinal-slo`:

- Uptime (últimos 30 dias — mensal rolling)
- Error budget consumido (%)
- p95 por endpoint principal
- Crash-free sessions
- Taxa de rollback

Acesso: `https://grafana.portalartfinal.com.br/d/slo/portalartfinal-slo`.

### 7.5 Revisão

- **Semanal:** SRE revisa SLOs na daily.
- **Mensal:** report de SLO para gestão (CTO).
- **Trimestral:** renegociar SLOs com produto se necessário.

---

## 8. Smoke test contínuo (canary)

### 8.1 Canary externo

UptimeRobot (ou CloudWatch canary) executa a cada 1 min:

```yaml
- name: api-health
  url: https://portalartfinal.com.br/api/v1/health
  method: GET
  expected_status: 200
  timeout: 5s
  alerting: PagerDuty se 3 falhas consecutivas

- name: home
  url: https://portalartfinal.com.br/up
  method: GET
  expected_status: 200
  timeout: 5s
  alerting: Slack
```

### 8.2 Synthetic transaction

A cada 5 min (canary Lambda ou script em instância dedicada):

```bash
#!/usr/bin/env bash
# Synthetic login + request autenticado
TOKEN=$(curl -sf -X POST https://portalartfinal.com.br/api/v1/auth/login \
    -H 'Content-Type: application/json' \
    -d '{"email":"canary@portalartfinal.com.br","password":"***","device_name":"canary","mode":"token"}' \
    | jq -r .access_token)

curl -fsS -H "Authorization: Bearer $TOKEN" \
    https://portalartfinal.com.br/api/v1/me \
    -o /dev/null -w '%{http_code} %{time_total}\n'
```

Resultado emitido como métrica CloudWatch `SyntheticAuthFlow.Latency`.

---

## 9. Retenção de logs e métricas

| Tipo                   | Retenção online | Retenção arquivo   |
| ---------------------- | --------------- | ------------------ |
| Logs JSON (stderr)     | 14 dias         | 90 dias S3 parquet |
| Pulse data             | 7 dias          | —                  |
| Horizon metrics        | 7 dias          | —                  |
| Sentry events          | 90 dias         | —                  |
| `activity_log` (DB)    | 2 anos          | S3 parquet         |
| `webhook_eventos` (DB) | 1 ano           | S3 parquet         |

---

## 10. Referências

- [`runbook-operations.md`](runbook-operations.md) — resposta aos alertas.
- [`engineering-standards.md`](engineering-standards.md) — padrões de código.
- [`ci-cd.md`](ci-cd.md) — release tracking no Sentry.
- [`PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md) §12.

---

## 11. Histórico de mudanças

| Versão | Data       | Autor  | Resumo                                  |
| ------ | ---------- | ------ | --------------------------------------- |
| 1.0.0  | 2026-04-18 | DevOps | Monitoramento e alertas inicial — draft |
