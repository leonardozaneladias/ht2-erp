---
title: Monitoramento e Alertas
version: 1.0.0
date: 2026-04-18
status: draft
stack: Laravel Pulse · Horizon · Sentry · Monolog JSON · Grafana
publico: DevOps, SRE, Engenharia
---

# Monitoramento e Alertas

Documento que define a estratégia de observabilidade: logs estruturados, métricas, dashboards, alertas, tracing funcional e SLO/SLI.

Base normativa:

- [`runbook-deploy.md`](runbook-deploy.md) — resposta aos alertas durante/após deploy
- [`conventions.md`](conventions.md) — padrões de código

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
    "message": "registro criado",
    "channel": "app",
    "context": {
        "request_id": "01HZX2B3C4D5E6F7",
        "correlation_id": "01HZX2AA111BBB22",
        "actor_type": "admin_user",
        "actor_id": 12345,
        "action": "RegistroCriado",
        "latency_ms": 67,
        "status": 201
    },
    "environment": "production",
    "release": "1.1.0+abc123"
}
```

### 1.2 `config/logging.php` — canal `app`

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
    private const BLOCKLIST = ['password', 'senha', 'token', 'access_token', 'secret'];
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

| Evento           | Campos extras obrigatórios                   |
| ---------------- | -------------------------------------------- |
| Request HTTP     | `endpoint`, `method`, `status`, `latency_ms` |
| Action executada | `action_class`, `result`, `duration_ms`      |
| Job executado    | `job_class`, `queue`, `attempts`, `duration_ms` |
| Cache hit/miss   | `cache_key`, `hit` (bool)                    |

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
</x-pulse>
```

### 2.2 Card custom — exemplo

Cards custom estendem `Laravel\Pulse\Livewire\Card`, leem agregados de séries gravadas via `Pulse::record()` e renderizam uma view própria. Use este padrão para qualquer métrica de negócio que precise de visibilidade no dashboard.

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Pulse;

use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
final class RegistrosPorMinuto extends Card
{
    public function render()
    {
        $registros = cache()->remember('pulse:registros_por_min', 30, fn () =>
            \App\Models\Registro::query()
                ->where('created_at', '>', now()->subHour())
                ->selectRaw('date_trunc(\'minute\', created_at) AS minuto, COUNT(*) AS qtd')
                ->groupBy('minuto')
                ->orderBy('minuto')
                ->get()
                ->map(fn ($row) => ['t' => $row->minuto, 'v' => $row->qtd])
        );

        return view('livewire.pulse.registros-por-minuto', [
            'registros' => $registros,
            'atual'     => $registros->last()['v'] ?? 0,
        ]);
    }
}
```

A série é alimentada via `Pulse::record('registro_criado', 1)` dentro da Action correspondente. Contadores de hit/miss seguem o mesmo padrão: `Pulse::record('idempotency', $hit ? 'hit' : 'miss')->count();`.

### 2.3 Gate de acesso

`app/Providers/PulseServiceProvider.php`:

```php
Gate::define('viewPulse', function (\HT2ML\Core\Models\AdminUser $user) {
    return $user->hasPermissionTo('admin.observability.view');
});
```

---

## 3. Horizon — dashboards

### 3.1 Gate

```php
// App\Providers\AuthServiceProvider
Gate::define('viewHorizon', fn (\HT2ML\Core\Models\AdminUser $user) =>
    $user->hasPermissionTo('admin.horizon.view')
);
```

### 3.2 Supervisores monitorados

| Supervisor          | Fila(s)                    | maxProcesses prod | Alerta se               |
| ------------------- | -------------------------- | ----------------- | ----------------------- |
| supervisor-default  | `default`, `notifications` | 20                | pending > 200 por 5 min |
| supervisor-emails   | `emails`                   | 6                 | pending > 50 por 2 min  |
| supervisor-exports  | `exports`                  | 2                 | pending > 5 por 30 min  |
| supervisor-pdf      | `pdf`                      | 4                 | pending > 50 por 2 min  |

### 3.3 Tags de jobs

```php
public function tags(): array
{
    return ['registro:' . $this->registroId];
}
```

Permite filtrar jobs por entidade no Horizon UI.

### 3.4 `horizon:snapshot`

Scheduled a cada 5 min — persiste métricas em Redis para histórico no dashboard.

### 3.5 LongWaitDetected

`config/horizon.php` → `waits`:

```php
'waits' => [
    'redis:default'       => 60,
    'redis:notifications' => 60,
    'redis:emails'        => 30,
    'redis:exports'       => 600,
    'redis:pdf'           => 120,
],
```

Horizon dispara `LongWaitDetected` event. Listener `NotifyLongWaitListener` envia alerta Slack (ver §4).

---

## 4. Alertas — tabela completa

### 4.1 Matriz de alertas

| ID  | Alerta                                | Condição                                                          | Janela | Canal                            | Severidade |
| --- | ------------------------------------- | ----------------------------------------------------------------- | ------ | -------------------------------- | ---------- |
| A01 | Falha massiva de jobs                 | > 10 `failed_jobs` em 5 min                                       | 5 min  | Slack `#alerts` + Sentry         | P1         |
| A02 | Fila travada                          | `pending > 50` por 2 min contínuos                                | 2 min  | Slack + PagerDuty                | P1         |
| A03 | 5xx em rotas web                      | taxa > 1% em 5 min                                                | 5 min  | PagerDuty + Slack                | P1         |
| A04 | Rate limit estourando                 | > 100 responses 429 por minuto                                    | 1 min  | Slack                            | P2         |
| A05 | Slow query PG                         | query > 1000ms (via Pulse)                                        | —      | Slack (baixa prio)               | P3         |
| A06 | Redis OOM                             | memory usage > 90%                                                | 1 min  | PagerDuty + Slack                | P1         |
| A07 | Horizon supervisor down               | qualquer supervisor em `stopped` ou `paused` > 5 min sem intenção | 5 min  | Slack                            | P1         |
| A08 | Disk usage                            | qualquer volume com free < 20%                                    | 5 min  | Slack                            | P2         |
| A09 | Backup PG falhou                      | cron `backup-pg.sh` exit-code != 0                                | —      | Slack + email DBA                | P1         |
| A10 | Sentry — release com crash-free < 99% | crash-free sessions < 99% na release atual                        | 15 min | Slack + PagerDuty                | P1         |
| A11 | Logins anômalos                       | > 500 logins por minuto (possível brute force)                    | 1 min  | Slack + security                 | P2         |

### 4.2 Configuração dos alertas

**Sentry (A03, A10):** configurado em Sentry UI → Alerts:

```
Name: 5xx rate > 1%
Environment: production
Conditions: event count > 1% of total events, 5 min window
Actions: Slack #alerts, PagerDuty escalation "primary"
```

**Horizon (A01, A02, A07):** implementados via `LongWaitDetected` event + custom listeners.

```php
// app/Providers/HorizonServiceProvider.php
public function boot(): void
{
    parent::boot();

    Horizon::routeSlackNotificationsTo(
        config('services.slack.webhook'),
        '#alerts'
    );

    Horizon::night();
}
```

**Pulse (A04, A05):** custom recorder + alerta via scheduled job checando agregados.

**Grafana/CloudWatch (A06, A08):** alertas de infra via CloudWatch → SNS → Slack/PagerDuty.

**Slack webhooks:** configurados em `.env` prod:

```dotenv
SLACK_WEBHOOK_ALERTS=https://hooks.slack.com/services/XXX/YYY/ZZZ
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
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Validation\ValidationException::class,
    ]);
})
```

Essas exceções são contabilizadas em métricas custom do Pulse, não em Sentry.

### 5.5 Fingerprint custom

Para agrupar melhor exceções de um mesmo tipo:

```php
\Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($exception) {
    if ($exception instanceof \App\Exceptions\OperacaoInvalidaException) {
        $scope->setFingerprint(['operacao-invalida', $exception->codigo]);
    }
});
```

---

## 6. Tracing funcional

### 6.1 Coluna `correlation_id`

Tabelas de negócio relevantes carregam uma coluna `correlation_id UUID/ULID` para permitir reconstruir uma jornada ponta a ponta. Aplique a coluna nas tabelas onde o rastreamento agregue valor de investigação.

### 6.2 Propagação

- Primeiro contato externo (request inicial) **gera** um `correlation_id`.
- Request interno pode trazer header `X-Correlation-Id`. Se vier, reutiliza; senão, gera.
- Toda action que persiste em tabela com `correlation_id` recebe o valor do `CorrelationContext` e grava.

### 6.3 Query de reconstrução

Reconstruir a jornada por `correlation_id` é uma união (`UNION ALL`) das tabelas envolvidas, ordenada por `created_at`:

```sql
SELECT 'registro' AS tipo, id::text AS ref, created_at
  FROM registros WHERE correlation_id = :c
UNION ALL
SELECT 'evento_dominio', id::text, created_at
  FROM eventos_dominio WHERE correlation_id = :c
ORDER BY created_at;
```

### 6.4 Integração com logs

Todo log carrega `correlation_id` via `CorrelationProcessor`. Para investigar incidente:

```bash
# Buscar todos os logs de uma jornada
grep '"correlation_id":"01HZX2AA111BBB22"' /var/log/app/*.log | \
    jq -r '[.timestamp, .level, .message, .context.endpoint] | @tsv'
```

### 6.5 Headers de resposta

Toda response expõe:

```
X-Request-Id: 01HZX2B3C4D5E6F7
X-Correlation-Id: 01HZX2AA111BBB22
```

Permite incluir esses valores em um bug report.

---

## 7. SLO / SLI

### 7.1 Tabela de SLOs

| SLI                                | Objetivo | Janela mensal  | Error budget / mês |
| ---------------------------------- | -------- | -------------- | ------------------ |
| Uptime                             | 99,5%    | calendar month | 3h 36min           |
| Latência p95                       | ≤ 500ms  | 30 dias        | —                  |
| Taxa de 5xx                        | ≤ 0,1%   | 30 dias        | —                  |
| MTTR (Mean Time To Recovery) P0/P1 | < 1h     | —              | —                  |
| Change failure rate                | ≤ 10%    | 30 dias        | —                  |
| Backup diário PG completo          | 100%     | 30 dias        | 0 falhas           |

### 7.2 Cálculo de error budget

Uptime 99,5% em mês de 30 dias:

```
total_minutos = 30 * 24 * 60 = 43.200 min
permitido     = 43.200 * 0,005 = 216 min = 3h 36min
```

Se error budget consumir 80% (172 min downtime), o time **para o deploy de features** e foca em estabilidade até recuperar.

### 7.3 Como coletamos cada SLI

| SLI                 | Fonte                                                  |
| ------------------- | ------------------------------------------------------ |
| Uptime              | Healthcheck externo (UptimeRobot ou CloudWatch canary) |
| Latência p95        | Pulse slow-requests + CloudWatch metrics               |
| Taxa 5xx            | Sentry aggregate + CloudWatch logs                     |
| MTTR                | Incident log manual                                    |
| Change failure rate | Releases com rollback / total releases                 |
| Backup              | Cron exit-code + S3 object existence                   |

### 7.4 Dashboard SLO

Grafana dashboard `app-slo`:

- Uptime (últimos 30 dias — mensal rolling)
- Error budget consumido (%)
- p95 por rota principal
- Crash-free sessions
- Taxa de rollback

### 7.5 Revisão

- **Semanal:** SRE revisa SLOs na daily.
- **Mensal:** report de SLO para gestão.
- **Trimestral:** renegociar SLOs com produto se necessário.

---

## 8. Smoke test contínuo (canary)

### 8.1 Canary externo

UptimeRobot (ou CloudWatch canary) executa a cada 1 min:

```yaml
- name: health
  url: https://exemplo.com.br/up
  method: GET
  expected_status: 200
  timeout: 5s
  alerting: PagerDuty se 3 falhas consecutivas
```

### 8.2 Synthetic transaction

A cada 5 min (canary Lambda ou script em instância dedicada), executar um fluxo autenticado representativo e emitir o resultado como métrica CloudWatch (ex.: `SyntheticFlow.Latency`). O fluxo deve cobrir login + uma operação de leitura simples, usando uma conta de canary dedicada.

---

## 9. Retenção de logs e métricas

| Tipo                | Retenção online | Retenção arquivo   |
| ------------------- | --------------- | ------------------ |
| Logs JSON (stderr)  | 14 dias         | 90 dias S3 parquet |
| Pulse data          | 7 dias          | —                  |
| Horizon metrics     | 7 dias          | —                  |
| Sentry events       | 90 dias         | —                  |
| `activity_log` (DB) | 2 anos          | S3 parquet         |

---

## 10. Referências

- [`runbook-deploy.md`](runbook-deploy.md) — procedimentos de deploy.
- [`conventions.md`](conventions.md) — padrões de código.
- [`ci-cd.md`](ci-cd.md) — release tracking no Sentry.

---

## 11. Histórico de mudanças

| Versão | Data       | Autor  | Resumo                                  |
| ------ | ---------- | ------ | --------------------------------------- |
| 1.0.0  | 2026-04-18 | DevOps | Monitoramento e alertas inicial — draft |
