# Middlewares core: AttachRequestId, EnsureSanctumAbility, IdempotencyKeyGuard, RateLimitByActor

**ID:** STORY-007
**Epic:** F1-E3 — Camada HTTP (routes + middlewares)
**Priority:** Must Have
**Story Points:** 3
**Status:** Not Started
**Skills:** `laravel-best-practices`, `laravel-security`, `php-best-practices`

## User Story

Como **desenvolvedor da equipe Portal ArtFinal**
Quero **ter os middlewares core de infraestrutura HTTP registrados e funcionais**
Para que **todas as requisições à API v1 recebam rastreabilidade, controle de abilities Sanctum, proteção contra duplicação e rate limiting por ator**

## Acceptance Criteria

- [ ] `app/Http/Middleware/AttachRequestId.php` existe, gera UUID v4 se `X-Request-ID` não vier no header e propaga o valor tanto no header da request quanto no header da response
- [ ] `app/Http/Middleware/EnsureSanctumAbility.php` existe, recebe abilities via construtor (ex: `new EnsureSanctumAbility('convites:read')`), verifica com `$request->user()->tokenCan($ability)` e retorna 403 se falhar
- [ ] `app/Http/Middleware/IdempotencyKeyGuard.php` existe, exige header `Idempotency-Key` em métodos POST e PATCH, armazena a chave no Redis com TTL de 86400 segundos (24h) e retorna a resposta cacheada se a chave já existir
- [ ] `app/Http/Middleware/RateLimitByActor.php` existe e aplica rate limit diferenciado por `auth()->id()` para requisições autenticadas e por IP para anônimas, usando `RateLimiter::for('api', ...)`
- [ ] Todos os quatro middlewares registrados em `bootstrap/app.php` via `$middleware->alias([...])`
- [ ] `AttachRequestId` aplicado globalmente ao grupo `api` via `$middleware->appendToGroup('api', AttachRequestId::class)`
- [ ] `RateLimitByActor` configurado no `RateLimiterServiceProvider` ou diretamente em `AppServiceProvider::boot()`
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros

## Technical Notes

### Arquivos a criar/modificar

- `app/Http/Middleware/AttachRequestId.php` — gera/propaga X-Request-ID
- `app/Http/Middleware/EnsureSanctumAbility.php` — verifica token abilities do Sanctum
- `app/Http/Middleware/IdempotencyKeyGuard.php` — idempotência via Redis (TTL 24h)
- `app/Http/Middleware/RateLimitByActor.php` — rate limit por ator autenticado ou IP
- `bootstrap/app.php` — registrar aliases e aplicar AttachRequestId ao grupo api
- `app/Providers/AppServiceProvider.php` — definir RateLimiter 'api' em `boot()`

### Observações técnicas

**AttachRequestId:**

```php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Ramsey\Uuid\Uuid; // ou Str::uuid()

final class AttachRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $request->headers->set('X-Request-ID', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
```

**EnsureSanctumAbility:**

```php
public function handle(Request $request, Closure $next, string ...$abilities): Response
{
    foreach ($abilities as $ability) {
        if (! $request->user()?->tokenCan($ability)) {
            abort(403, 'Token não possui a ability requerida: '.$ability);
        }
    }
    return $next($request);
}
```

Uso na rota: `->middleware('ability:convites:read')`

**IdempotencyKeyGuard — Redis schema:**

- Chave Redis: `idempotency:{userId}:{idempotencyKey}`
- Valor: JSON serializado da resposta (`status`, `headers`, `body`)
- TTL: 86400 segundos
- Se chave existe: retornar `response()->json(json_decode($cached['body']), $cached['status'])`
- Se chave não existe: executar request, armazenar resultado antes de retornar

**RateLimitByActor:**

```php
// Em AppServiceProvider::boot()
RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(120)->by('user:'.$request->user()->id)
        : Limit::perMinute(20)->by('ip:'.$request->ip());
});
```

**Registro em bootstrap/app.php:**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('api', \App\Http\Middleware\AttachRequestId::class);
    $middleware->alias([
        'ability'      => \App\Http\Middleware\EnsureSanctumAbility::class,
        'idempotency'  => \App\Http\Middleware\IdempotencyKeyGuard::class,
        'rate.actor'   => \App\Http\Middleware\RateLimitByActor::class,
        'webhook.hmac' => \App\Http\Middleware\ValidateWebhookHmac::class,
    ]);
})
```

## Dependencies

- **Blocked by:** STORY-006 (routes skeleton deve existir para testes de integração)
- **Blocks:** STORY-008

## Testing Requirements

- [ ] Teste feature: requisição à API sem `X-Request-ID` retorna header `X-Request-ID` na resposta
- [ ] Teste feature: requisição à API com `X-Request-ID: abc-123` retorna o mesmo valor no header da resposta
- [ ] Teste unit: `EnsureSanctumAbility` retorna 403 para token sem a ability requerida
- [ ] Teste unit: `EnsureSanctumAbility` passa para token com a ability requerida
- [ ] Teste feature: POST duplicado com o mesmo `Idempotency-Key` retorna a resposta cacheada sem reprocessar
- [ ] Teste feature: `RateLimitByActor` retorna 429 após exceder limite por ator
- [ ] `php artisan test --compact --filter=STORY007` verde
