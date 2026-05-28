# Routes skeleton — api/v1.php e webhook.php

**ID:** STORY-006
**Epic:** F1-E3 — Camada HTTP (routes + middlewares)
**Priority:** Must Have
**Story Points:** 2
**Status:** Not Started
**Skills:** `laravel-routes-best-practices`, `laravel-best-practices`

## User Story

Como **desenvolvedor da equipe Portal ArtFinal**
Quero **ter o skeleton de rotas da API v1 e do webhook registrados no bootstrap da aplicação**
Para que **todas as features futuras possam adicionar seus endpoints sem precisar alterar a estrutura de roteamento**

## Acceptance Criteria

- [ ] `routes/api/v1.php` existe e contém grupos comentados para Auth, Me, Convites, Rsvp, Seating, Extras, Pagamentos, Enquetes e Eventos — cada um com `// TODO: implementar em Fases posteriores`
- [ ] `routes/webhook.php` existe com um grupo prefixado `/webhook` sem CSRF e com middleware placeholder `webhook.hmac`
- [ ] `bootstrap/app.php` registra `routes/api/v1.php` via `withRouting(apiPrefix: 'api/v1', api: base_path('routes/api/v1.php'))` (ou equivalente Laravel 13)
- [ ] `routes/webhook.php` é registrado em `bootstrap/app.php` usando `then:` callback de `withRouting`
- [ ] Rotas de webhook são excluídas do CSRF via `$middleware->validateCsrfTokens(except: ['webhook/*'])`
- [ ] `php artisan route:list --path=api/v1` não retorna erro e exibe os grupos (mesmo que vazios)
- [ ] `php artisan route:list --path=webhook` não retorna erro
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros

## Technical Notes

### Arquivos a criar/modificar

- `routes/api/v1.php` — novo arquivo; grupos Route::prefix com comentários TODO por domínio
- `routes/webhook.php` — novo arquivo; Route::prefix('webhook')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
- `bootstrap/app.php` — modificar `withRouting()` para incluir api/v1 e webhook via callback `then:`

### Observações técnicas

No Laravel 13 com `bootstrap/app.php` funcional, `withRouting()` aceita:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api/v1.php',
    apiPrefix: 'api/v1',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        Route::middleware('api')
            ->prefix('webhook')
            ->group(base_path('routes/webhook.php'));
    },
)
```

O middleware `webhook.hmac` será criado em F2; por ora registrar como placeholder no arquivo de alias em `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'webhook.hmac' => \App\Http\Middleware\ValidateWebhookHmac::class,
    ]);
})
```

A classe `ValidateWebhookHmac` pode ser um stub que apenas chama `$next($request)` por enquanto.

Estrutura mínima de `routes/api/v1.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Auth
Route::prefix('auth')->group(function (): void {
    // TODO: implementar em Fases posteriores
});

// Me
Route::prefix('me')->middleware('auth:sanctum')->group(function (): void {
    // TODO: implementar em Fases posteriores
});

// Convites
Route::prefix('convites')->group(function (): void {
    // TODO: implementar em Fases posteriores
});

// Rsvp
Route::prefix('rsvp')->group(function (): void {
    // TODO: implementar em Fases posteriores
});

// Seating
Route::prefix('seating')->middleware('auth:sanctum')->group(function (): void {
    // TODO: implementar em Fases posteriores
});

// Extras
Route::prefix('extras')->middleware('auth:sanctum')->group(function (): void {
    // TODO: implementar em Fases posteriores
});

// Pagamentos
Route::prefix('pagamentos')->middleware('auth:sanctum')->group(function (): void {
    // TODO: implementar em Fases posteriores
});

// Enquetes
Route::prefix('enquetes')->middleware('auth:sanctum')->group(function (): void {
    // TODO: implementar em Fases posteriores
});

// Eventos
Route::prefix('eventos')->middleware('auth:sanctum')->group(function (): void {
    // TODO: implementar em Fases posteriores
});
```

## Dependencies

- **Blocked by:** STORY-001 (Setup inicial — bootstrap/app.php deve existir)
- **Blocks:** STORY-007, STORY-008

## Testing Requirements

- [ ] `php artisan route:list --path=api/v1` executa sem exceção
- [ ] `php artisan route:list --path=webhook` executa sem exceção
- [ ] `php artisan test --compact --filter=STORY006` verde
- [ ] Teste feature: `GET /api/v1/qualquer-rota-inexistente` retorna 404 (não 500), confirmando que o roteamento está registrado
