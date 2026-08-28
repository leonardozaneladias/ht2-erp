<?php

declare(strict_types=1);

use HT2ML\Core\Support\Modules\EscopoDeRota;
use HT2ML\Core\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks contribuídos por módulos
|--------------------------------------------------------------------------
|
| Fora do grupo `web`: quem chama é uma máquina, que não tem cookie para mandar
| e por isso não tem como passar pelo CSRF. Sessão aqui seria sessão criada e
| descartada a cada chamada.
|
| O prefixo `/webhooks` e o name `webhooks.` são impostos, não sugeridos. Um
| endpoint sem autenticação precisa ser reconhecível no `route:list`, e sem o
| prefixo um módulo poderia pendurar uma rota sem auth em qualquer lugar da
| aplicação — inclusive sob /admin, onde ninguém procuraria por ela.
|
| O throttle vem do limiter nomeado `webhooks`, registrado pelo core e
| substituível pelo produto com um RateLimiter::for('webhooks', ...) próprio.
|
*/

Route::prefix('webhooks')
    ->name('webhooks.')
    ->middleware('throttle:webhooks')
    ->group(function (): void {
        foreach (ModuleRegistry::routeCallbacks(EscopoDeRota::Webhook) as $registrarRotasDoModulo) {
            $registrarRotasDoModulo();
        }
    });
