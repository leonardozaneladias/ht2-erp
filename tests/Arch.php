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

// Auditoria automática obrigatória: todo model novo usa o trait Auditavel
// (captura created/updated/deleted no activity_log) — ou entra na whitelist
// abaixo COM justificativa. Ver docs/devops/conventions.md §7.2.
arch('models usam Auditavel ou estão na whitelist consciente')
    ->expect('App\Models')
    ->toUseTrait(App\Models\Concerns\Auditavel::class)
    ->ignoring([
        'App\Models\Concerns',                    // os próprios traits
        App\Models\Activity::class,               // o log em si (append-only)
        App\Models\LoginHistory::class,           // histórico append-only (já é trilha)
        App\Models\User::class,                   // scaffold do guard web (portal futuro decidirá)
        App\Models\DocumentSequence::class,       // contador técnico de infra, não entidade de negócio
    ]);
