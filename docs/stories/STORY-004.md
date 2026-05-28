# Service Providers: RateLimiter, Gateway, DomainEvent e Auth

**ID:** STORY-004  
**Epic:** F1-E2 — Infraestrutura de domínio  
**Priority:** Must Have  
**Story Points:** 3  
**Status:** Not Started  
**Skills:** `laravel-providers`, `laravel-best-practices`

## User Story

Como **desenvolvedor do Portal ArtFinal**
Quero **ter Service Providers dedicados para rate limiting, gateway de pagamento, eventos de domínio e autorização**
Para que **cada responsabilidade de bootstrap da aplicação esteja encapsulada em seu provider, facilitando manutenção, testes e ativação/desativação por contexto**

## Acceptance Criteria

- [ ] `app/Providers/RateLimiterServiceProvider.php` existe e define no mínimo os limiters: `api` (60 req/min por IP), `portal-adesao` (10 req/min por IP — protege o wizard de adesão), `admin-login` (5 tentativas/min por IP)
- [ ] `app/Providers/GatewayServiceProvider.php` existe e registra a interface `App\Contracts\Gateway\PaymentGatewayInterface` vinculada à implementação concreta lida de `config('services.gateway.driver')` — usa `$this->app->bind()`, não `singleton` (facilitará swap em testes)
- [ ] `app/Contracts/Gateway/PaymentGatewayInterface.php` existe com `declare(strict_types=1)` e ao menos os métodos de assinatura: `createPixCharge(...)`, `createBoletoCharge(...)`, `createCardCharge(...)` (assinaturas a serem finalizadas em F2)
- [ ] `app/Providers/DomainEventServiceProvider.php` existe, estende `Illuminate\Foundation\Support\Providers\EventServiceProvider`, tem array `$listen` vazio mas com comentário indicando onde registrar os eventos por bounded context
- [ ] `app/Providers/AuthServiceProvider.php` existe, registra `Gate::define('viewHorizon', ...)` para permitir acesso ao `/horizon` apenas para AdminUsers, registra `Gate::define('viewPulse', ...)` para acesso ao `/pulse`
- [ ] `app/Providers/AuthServiceProvider.php` tem array `$policies` vazio pronto para receber as Policies dos Models (a serem adicionadas em F1-E4 e F1-E5)
- [ ] `bootstrap/providers.php` lista os 4 novos providers na ordem: `AppServiceProvider`, `HorizonServiceProvider`, seguidos de `RateLimiterServiceProvider`, `GatewayServiceProvider`, `DomainEventServiceProvider`, `AuthServiceProvider`
- [ ] `php artisan route:list` não lança erros após o registro dos providers
- [ ] `php artisan config:clear && php artisan optimize:clear` executa sem exceções
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros neste arquivo

## Technical Notes

### Arquivos a criar/modificar

- `app/Providers/RateLimiterServiceProvider.php` — criar; usar `RateLimiter::for()` no método `boot()`
- `app/Providers/GatewayServiceProvider.php` — criar; usar `$this->app->bind()` no método `register()`
- `app/Providers/DomainEventServiceProvider.php` — criar; estender `EventServiceProvider`, não `ServiceProvider`
- `app/Providers/AuthServiceProvider.php` — criar; definir Gates no método `boot()`
- `app/Contracts/Gateway/PaymentGatewayInterface.php` — criar; interface stub para o gateway
- `bootstrap/providers.php` — atualizar lista de providers

### Observações técnicas

- O `RateLimiterServiceProvider` deve usar `RateLimiter::for()` no `boot()`, **não** no `register()`. Rate limiters dependem de outros serviços (ex: `Request`) que ainda não estão resolvidos em `register()`.
- O `GatewayServiceProvider` usa `bind()` (não `singleton()`) propositalmente — isso permite que testes sobrescrevam o binding com `$this->app->bind(PaymentGatewayInterface::class, MockGateway::class)` sem efeitos colaterais entre testes.
- O `DomainEventServiceProvider` estende `Illuminate\Foundation\Support\Providers\EventServiceProvider` (não o `ServiceProvider` base) para herdar o método `discoverEvents()` e a auto-discovery de listeners, que será útil nas Sprints de gateway (F2+).
- O `AuthServiceProvider` **não** deve herdar de `Illuminate\Foundation\Support\Providers\AuthServiceProvider` (que foi removido no Laravel 11+). Usar `Illuminate\Support\ServiceProvider` e registrar Gates manualmente no `boot()`.
- O `Gate::define('viewHorizon')` deve checar `$user instanceof AdminUser && $user->ativo === true` — bloqueia contas desativadas.
- Não criar uma implementação concreta de `PaymentGatewayInterface` nesta story — apenas a interface stub. A implementação Itaú é escopo de F2/F3.
- A variável `config('services.gateway.driver')` deve ser adicionada ao `config/services.php` e ao `.env.example` como `PAYMENT_GATEWAY_DRIVER=mock` para o ambiente local.

### Exemplo de estrutura do RateLimiterServiceProvider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimiterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit =>
            Limit::perMinute(60)->by($request->ip())
        );

        RateLimiter::for('portal-adesao', fn (Request $request): Limit =>
            Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for('admin-login', fn (Request $request): Limit =>
            Limit::perMinute(5)->by($request->ip())
        );
    }
}
```

## Dependencies

- **Blocked by:** STORY-001 (pacotes base), STORY-002 (guards configurados — AuthServiceProvider depende de `AdminUser`)
- **Blocks:** STORY-005 (Horizon requer providers registrados), F1-E3 (middlewares de rate limit referenciam os limiters nomeados)

## Testing Requirements

- [ ] `php artisan test --compact --filter=STORY004` verde
- [ ] Teste de feature: requisição POST para uma rota protegida com `throttle:portal-adesao` lança `429` após 11 tentativas seguidas
- [ ] Teste de feature: `Gate::allows('viewHorizon', $adminUser)` retorna `true` quando `$adminUser->ativo = true`
- [ ] Teste de feature: `Gate::allows('viewHorizon', $adminUser)` retorna `false` quando `$adminUser->ativo = false`
- [ ] Teste unitário: `app(PaymentGatewayInterface::class)` não lança exceção de resolução (binding registrado)
- [ ] `php artisan optimize:clear` sem erros após registrar os 4 providers
