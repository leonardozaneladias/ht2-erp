<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/*
 * Spinners de animação contínua (animate-spin) devem respeitar prefers-reduced-motion
 * (variante motion-reduce:animate-none), assim como os skeletons já fazem. Animação
 * giratória ininterrupta pode causar desconforto vestibular; reduced motion é piso
 * mínimo de acessibilidade. Cada `animate-spin` precisa de seu `motion-reduce:animate-none`.
 */

function assertSpinnerRespeitaReducedMotion(string $html): void
{
    $giros = substr_count($html, 'animate-spin');

    expect($giros)->toBeGreaterThan(0)
        ->and(substr_count($html, 'motion-reduce:animate-none'))->toBe($giros);
}

it('spinner base respeita prefers-reduced-motion', function (): void {
    assertSpinnerRespeitaReducedMotion(Blade::render('<x-shared.spinner />'));
});

it('button em estado loading respeita prefers-reduced-motion', function (): void {
    assertSpinnerRespeitaReducedMotion(Blade::render('<x-shared.button :loading="true">Salvar</x-shared.button>'));
});

it('loading-button respeita prefers-reduced-motion', function (): void {
    assertSpinnerRespeitaReducedMotion(Blade::render('<x-shared.loading-button>Salvar</x-shared.loading-button>'));
});

it('overlay de loading da tabela respeita prefers-reduced-motion', function (): void {
    assertSpinnerRespeitaReducedMotion(Blade::render('<x-admin.table.table><tbody></tbody></x-admin.table.table>'));
});

it('spinner de busca do cep-input respeita prefers-reduced-motion', function (): void {
    assertSpinnerRespeitaReducedMotion(Blade::render('<x-shared.cep-input name="cep" />'));
});
