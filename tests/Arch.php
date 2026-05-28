<?php

declare(strict_types=1);

// Todos os arquivos PHP da app têm strict_types
arch('todos os arquivos PHP da app têm declare(strict_types=1)')
    ->expect('App')
    ->toUseStrictTypes();

// Services e Actions não recebem/retornam HTTP
arch('services não retornam respostas HTTP')
    ->expect('App\Services')
    ->not->toUse([
        'Illuminate\Http\JsonResponse',
        'Illuminate\Http\RedirectResponse',
    ]);

arch('actions não retornam respostas HTTP')
    ->expect('App\Actions')
    ->not->toUse([
        'Illuminate\Http\JsonResponse',
        'Illuminate\Http\RedirectResponse',
    ]);
