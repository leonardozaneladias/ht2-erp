<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('usa wire:click no botão primário por padrão (modo retrocompatível)', function (): void {
    // Sem a prop submit, o footer mantém o disparo por wire:click — comportamento
    // idêntico ao de todas as telas de form existentes (nenhuma regressão).
    $html = Blade::render('<x-admin.form-footer cancel-href="/admin/exemplos" />');

    expect($html)
        ->toContain('wire:click="salvar"')
        ->not->toContain('type="submit"');
});

it('emite type="submit" e omite wire:click quando submit está ativo', function (): void {
    // Dentro de um <form wire:submit="salvar">, o botão primário precisa ser
    // type="submit" (e NÃO wire:click) para o Enter submeter o form e o clique não
    // disparar salvar duas vezes (submit do form + wire:click).
    $html = Blade::render('<x-admin.form-footer cancel-href="/admin/exemplos" submit />');

    expect($html)
        ->toContain('type="submit"')
        ->not->toContain('wire:click="salvar"');
});

it('honra uma action customizada no wire:target do estado de loading', function (): void {
    $html = Blade::render('<x-admin.form-footer cancel-href="/admin/exemplos" action="gravar" submit />');

    expect($html)
        ->toContain('type="submit"')
        ->toContain('wire:target="gravar"');
});

it('preserva label, loadingLabel e o link de cancelar em ambos os modos', function (): void {
    $html = Blade::render(
        '<x-admin.form-footer cancel-href="/admin/exemplos" label="Criar" loading-label="Criando..." submit />',
    );

    expect($html)
        ->toContain('Criar')
        ->toContain('Criando...')
        ->toContain('/admin/exemplos')
        ->toContain('wire:loading');
});
