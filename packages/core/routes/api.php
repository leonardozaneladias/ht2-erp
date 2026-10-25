<?php

declare(strict_types=1);

use HT2ML\Core\Support\Modules\EscopoDeRota;
use HT2ML\Core\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de API contribuídas por módulos
|--------------------------------------------------------------------------
|
| No grupo `api` do Laravel: sem sessão, sem CSRF, e com o que o produto
| pendura no grupo (o skeleton pendura `throttle:api`). Quem chama é um app
| ou uma integração, não um navegador com cookie.
|
| O prefixo `/api` e o name `api.` são impostos, como no webhook: uma rota sem
| sessão precisa ser reconhecível no `route:list`. A versão (`/v1`) e a
| autenticação ficam com o módulo — o core não sabe quem é o usuário de uma
| API, e não deveria saber.
|
*/

Route::prefix('api')
    ->name('api.')
    ->middleware('api')
    ->group(function (): void {
        foreach (ModuleRegistry::routeCallbacks(EscopoDeRota::Api) as $registrarRotasDoModulo) {
            $registrarRotasDoModulo();
        }
    });
