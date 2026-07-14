<?php

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * Regressão: o AppServiceProvider registra
 *   Paginator::defaultView('vendor.pagination.inspinia')
 *   Paginator::defaultSimpleView('vendor.pagination.simple-inspinia')
 * mas essas views não existiam — qualquer ->links() (notificações, auditoria,
 * histórico de acesso, vitrine) quebrava com "View [...] not found".
 */
it('registra as views de paginação default e elas existem', function () {
    expect(view()->exists('vendor.pagination.inspinia'))->toBeTrue();
    expect(view()->exists('vendor.pagination.simple-inspinia'))->toBeTrue();
});

it('renderiza a paginação completa sem lançar e com semântica acessível', function () {
    $items = collect(range(1, 30))->forPage(2, 10);
    $paginator = new LengthAwarePaginator($items, 30, 10, 2, ['path' => '/admin/exemplo']);

    $html = (string) $paginator->links();

    expect($html)
        ->toContain('role="navigation"')
        ->toContain('aria-current="page"') // página atual destacada
        ->toContain('aria-label="Ir para a página 1"'); // link para outra página
});

it('renderiza a paginação simples sem lançar e com prev/next', function () {
    $paginator = new Paginator(collect(range(1, 6)), 5, 1, ['path' => '/admin/exemplo']);

    $html = (string) $paginator->links();

    expect($html)
        ->toContain('role="navigation"')
        ->toContain('rel="next"');
});

/*
|--------------------------------------------------------------------------
| Paginação dentro de componentes Livewire (WithPagination)
|--------------------------------------------------------------------------
|
| O trait WithPagination reescreve o defaultView para `livewire::tailwind`
| em runtime, então as telas Livewire paginadas (notificações, auditoria,
| histórico de acesso + futuras) ignoram a view Inspinia do AppServiceProvider
| e caíam na view genérica do vendor (cores tailwind cruas + aria-label em
| inglês). O projeto sobrescreve `livewire::tailwind`/`simple-tailwind` em
| resources/views/vendor/livewire/ com o tema Inspinia + PT-BR, preservando o
| disparo SPA (wire:click/gotoPage), de forma idêntica à inspinia.blade.php.
*/
it('renderiza a paginação Livewire com tema Inspinia e idioma PT-BR', function () {
    $items = collect(range(1, 30))->forPage(2, 10);
    $paginator = new LengthAwarePaginator($items, 30, 10, 2, ['path' => '/admin/exemplo']);

    $html = (string) $paginator->links('livewire::tailwind');

    expect($html)
        ->toContain('aria-label="Navegação de páginas"')      // PT-BR (CLAUDE.md §4)
        ->not->toContain('aria-label="Pagination Navigation"') // sem o inglês do vendor
        ->toContain('aria-current="page"')                     // página atual destacada
        ->toContain('bg-primary')                              // token do tema Inspinia
        ->toContain('text-body-color')                         // token do tema Inspinia
        ->not->toContain('text-gray-700');                     // sem as classes genéricas do vendor
});

it('preserva o disparo SPA na paginação Livewire (wire:click/gotoPage, sem href)', function () {
    $items = collect(range(1, 30))->forPage(2, 10);
    $paginator = new LengthAwarePaginator($items, 30, 10, 2, ['path' => '/admin/exemplo']);

    $html = (string) $paginator->links('livewire::tailwind');

    expect($html)
        ->toContain('wire:click="gotoPage(')
        ->toContain('wire:click="previousPage(')
        ->toContain('wire:click="nextPage(')
        ->toContain('aria-label="Ir para a página 1"') // link para outra página, nomeado
        ->not->toContain('href='); // não recai em navegação full-page (perderia o SPA)
});

it('renderiza a paginação Livewire simples com tema Inspinia e PT-BR', function () {
    $paginator = new Paginator(collect(range(1, 6)), 5, 1, ['path' => '/admin/exemplo']);

    $html = (string) $paginator->links('livewire::simple-tailwind');

    expect($html)
        ->toContain('aria-label="Navegação de páginas"')
        ->not->toContain('aria-label="Pagination Navigation"')
        ->toContain('wire:click="nextPage(')
        ->not->toContain('href='); // botões Livewire, não âncoras
});
