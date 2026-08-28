<?php

declare(strict_types=1);

use HT2ML\Core\Support\Modules\EscopoDeRota;
use HT2ML\Core\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Um módulo tem três destinos de rota, não um
|--------------------------------------------------------------------------
|
| Até 2026-08-28 o único destino era o grupo autenticado de /admin. Serve para
| tela de cadastro e para mais nada: um módulo de pagamentos precisa de um
| endpoint que o gateway chama sem sessão e sem CSRF, e um de matrícula precisa
| de uma página que o responsável abre sem estar logado. Sem canal, os dois
| viram edição no routes/web.php do produto — a dependência de mão dupla que o
| ADR-0022 proíbe.
|
| O que estes testes guardam não é "o enum existe": é que cada escopo entrega o
| que promete. Um webhook que herdasse o middleware `web` responderia 419 ao
| gateway (CSRF), e o sintoma seria "o gateway diz que entregou e nada acontece"
| — o pior tipo de bug para depurar, porque o log da aplicação fica limpo.
|
*/

beforeEach(function (): void {
    ModuleRegistry::flush();
});

afterEach(function (): void {
    ModuleRegistry::flush();
});

it('separa os callbacks por escopo, sem misturar', function (): void {
    ModuleRegistry::routes(fn () => null);
    ModuleRegistry::routes(fn () => null, EscopoDeRota::Publico);
    ModuleRegistry::routes(fn () => null, EscopoDeRota::Webhook);
    ModuleRegistry::routes(fn () => null, EscopoDeRota::Webhook);

    expect(ModuleRegistry::routeCallbacks())->toHaveCount(1)
        ->and(ModuleRegistry::routeCallbacks(EscopoDeRota::Publico))->toHaveCount(1)
        ->and(ModuleRegistry::routeCallbacks(EscopoDeRota::Webhook))->toHaveCount(2);
});

it('o default é Admin, que é o que as chamadas anteriores ao escopo significavam', function (): void {
    ModuleRegistry::routes(fn () => null);

    expect(ModuleRegistry::routeCallbacks(EscopoDeRota::Admin))->toHaveCount(1)
        ->and(ModuleRegistry::escoposComRotas())->toBe([EscopoDeRota::Admin]);
});

it('escoposComRotas ignora o que ninguém contribuiu', function (): void {
    expect(ModuleRegistry::escoposComRotas())->toBe([]);

    ModuleRegistry::routes(fn () => null, EscopoDeRota::Webhook);

    expect(ModuleRegistry::escoposComRotas())->toBe([EscopoDeRota::Webhook]);
});

it('o core carrega um hospedeiro para cada escopo', function (): void {
    // Sem o arquivo, o callback do módulo nunca é executado e a rota não existe
    // — sem erro, sem log, só 404.
    foreach (['admin', 'publico', 'webhook'] as $arquivo) {
        expect(is_file(dirname(__DIR__, 3) . "/packages/core/routes/{$arquivo}.php"))
            ->toBeTrue("Falta packages/core/routes/{$arquivo}.php");
    }

    $provider = (string) file_get_contents(
        dirname(__DIR__, 3) . '/packages/core/src/CoreServiceProvider.php',
    );

    expect($provider)->toContain('routes/admin.php')
        ->and($provider)->toContain('routes/publico.php')
        ->and($provider)->toContain('routes/webhook.php');
});

it('o webhook nasce sob /webhooks, com throttle e sem a stack web', function (): void {
    ModuleRegistry::routes(function (): void {
        Route::post('/pagamento', fn (): string => 'ok')->name('pagamento');
    }, EscopoDeRota::Webhook);

    require dirname(__DIR__, 3) . '/packages/core/routes/webhook.php';

    $rota = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($r): bool => $r->getName() === 'webhooks.pagamento');

    expect($rota)->not->toBeNull('a rota do módulo não foi registrada');

    // O prefixo é imposto, não sugerido: rota sem autenticação precisa ser
    // reconhecível no route:list, e sem ele um módulo poderia pendurar uma rota
    // aberta sob /admin, onde ninguém procuraria por ela.
    expect($rota->uri())->toBe('webhooks/pagamento')
        ->and($rota->gatherMiddleware())->toContain('throttle:webhooks')
        // Se herdasse `web`, o CSRF responderia 419 ao gateway e o log da
        // aplicação ficaria limpo — o pior tipo de bug para depurar.
        ->and($rota->gatherMiddleware())->not->toContain('web');
});

it('a rota pública nasce com a stack web e sem prefixo imposto', function (): void {
    ModuleRegistry::routes(function (): void {
        Route::get('/matricula', fn (): string => 'ok')->name('matricula');
    }, EscopoDeRota::Publico);

    Route::middleware('web')->group(dirname(__DIR__, 3) . '/packages/core/routes/publico.php');

    $rota = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($r): bool => $r->getName() === 'matricula');

    expect($rota)->not->toBeNull('a rota pública do módulo não foi registrada')
        // Sem prefixo: quem escolhe a URL de uma página pública é o módulo.
        ->and($rota->uri())->toBe('matricula')
        ->and($rota->gatherMiddleware())->toContain('web')
        // E sem autenticação, que é o ponto de existir este escopo.
        ->and($rota->gatherMiddleware())->not->toContain('admin.auth');
});

it('o limiter de webhooks existe antes de qualquer módulo registrar um', function (): void {
    // Um throttle apontando para limiter inexistente estoura no primeiro
    // request do gateway, não no deploy — e aí já é produção.
    expect(RateLimiter::limiter('webhooks'))->not->toBeNull();
});
