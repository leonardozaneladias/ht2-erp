<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza o input de CEP com máscara, placeholder e ícone de pino', function (): void {
    $html = Blade::render('<x-shared.cep-input name="cep" wire:model="cep" />');

    expect($html)
        // wrapper que o forms.js usa para localizar o loader
        ->toContain('data-af-cep-field')
        // ícone do campo (input interno) e máscara de CEP serializada no dataset
        ->toContain('tabler--map-pin')
        ->toContain('99999-999')
        ->toContain('00000-000') // placeholder
        // binding repassado via $attributes ao input interno
        ->toContain('wire:model="cep"')
        // gancho do loader
        ->toContain('data-cep-loading');
});

it('expõe o estado de carregamento via role=status com nome acessível e ícone decorativo', function (): void {
    $html = Blade::render('<x-shared.cep-input name="cep" />');

    // o container do loader é uma live region (role="status") — espelha o
    // x-shared.spinner; dá nome acessível ao estado transitório de busca
    expect($html)->toMatch('/data-cep-loading[^>]*role="status"/');

    // o spinner tabler--loader-2 é puramente decorativo → escondido do leitor de
    // tela (evita anúncio de ícone mudo; o nome vem do texto sr-only)
    expect($html)->toMatch('/<i[^>]*tabler--loader-2[^>]*aria-hidden="true"/');

    // o nome acessível do estado de loading fica num texto visualmente oculto
    expect($html)->toMatch('/<span class="sr-only">Buscando endereço/');
});

it('repassa atributos (wire:model, required) ao input interno e respeita o label customizado', function (): void {
    $html = Blade::render(
        '<x-shared.cep-input name="cep_entrega" label="CEP de entrega" required wire:model.live="cepEntrega" />',
    );

    expect($html)
        ->toContain('CEP de entrega')
        ->toContain('wire:model.live="cepEntrega"')
        ->toContain('text-danger'); // asterisco de obrigatório propagado ao input
});

it('serializa target e eventName para o dataset af-cep consumido pelo forms.js', function (): void {
    $html = Blade::render(
        '<x-shared.cep-input name="cep" target="preencherEndereco" event-name="cep-ok" />',
    );

    expect($html)
        ->toContain('preencherEndereco')
        ->toContain('cep-ok');
});
