<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ht2ml/extensao-api
|--------------------------------------------------------------------------
|
| O produto publica e ajusta (`vendor:publish --tag=api-config`). Tudo aqui é
| contrato público da API do produto: mudar um valor muda o que o app recebe.
|
*/

return [

    /*
    | Onde o envelope de erro vale. Fora deste padrão (admin, Livewire, páginas
    | públicas) o handler não toca na exceção — o Livewire também "expectsJson"
    | em requisições AJAX, e competir com ele quebraria o painel.
    */
    'prefixo' => 'api/*',

    'paginacao' => [
        // Itens por página quando o cliente não pede (?por_pagina).
        'padrao' => 25,
        // O máximo que o cliente consegue pedir. Clamp dos dois lados: 0, -1
        // e "abc" não descrevem página nenhuma.
        'teto' => 100,
    ],

    /*
    | Idiomas que a API entende, como o cliente os manda => locale do Laravel.
    | Fonte única: o DefinirIdioma lê daqui, e uma validação de "idioma
    | preferido" deve usar DefinirIdioma::idiomasSuportados() — duas listas
    | divergiriam, e o usuário veria a preferência "salva" sem efeito nenhum.
    */
    'idiomas' => [
        'pt' => 'pt_BR',
        'pt_BR' => 'pt_BR',
        'en' => 'en',
        'es' => 'es',
    ],

    // Limiter `api` (o skeleton pendura `throttle:api` no grupo). O produto
    // que registrar o próprio RateLimiter::for('api') depois deste vence.
    'limite_por_minuto' => 120,

    'openapi' => [
        // Onde `php artisan api:openapi` grava a especificação versionada.
        'arquivo' => 'docs/openapi.json',
    ],

];
