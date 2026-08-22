<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// Acessibilidade — os ícones do file-upload são puramente decorativos: acompanham
// sempre um texto ("Clique para selecionar…", "Selecionar arquivo", "Arraste
// arquivos…") e o nome do campo vem do <label for>. Logo recebem aria-hidden="true"
// (mesma convenção de button/dropdown-item/kpi-card/unsaved-bar). O único caso de
// ícone-sozinho — o botão de remover do preview do Dropzone — ganha aria-label
// próprio (em vez de ficar mudo para o leitor de tela). Componente de upload é a
// fundação das telas de branding/configuração (logo, favicon, login).

it('modo card: ícones de placeholder (vazio e preview) são decorativos', function (): void {
    $html = Blade::render('<x-shared.file-upload name="logo" label="Logo" />');

    expect($html)
        ->toMatch('/<i class="iconify tabler--cloud-upload[^>]*aria-hidden="true"/')
        ->toMatch('/<i class="iconify tabler--file[^>]*aria-hidden="true"/')
        ->toContain('Clique para selecionar um arquivo'); // regressão: nome visível em texto
});

it('variante compact: ícones de thumbnail e de seleção são decorativos', function (): void {
    $html = Blade::render('<x-shared.file-upload name="favicon" variant="compact" />');

    expect($html)
        ->toMatch('/<i class="iconify tabler--photo[^>]*aria-hidden="true"/')
        ->toMatch('/<i class="iconify tabler--upload[^>]*aria-hidden="true"/')
        ->toContain('Selecionar arquivo');
});

it('modo dropzone: ícone de upload é decorativo e o botão de remover ganha nome', function (): void {
    $html = Blade::render('<x-shared.file-upload name="anexos" mode="dropzone" :multiple="true" />');

    expect($html)
        ->toMatch('/<i class="iconify tabler--cloud-upload[^>]*aria-hidden="true"/')
        // botão icon-only do template do Dropzone: nome via aria-label, ícone escondido
        ->toContain('data-dz-remove')
        ->toContain('aria-label="Remover arquivo"')
        ->toMatch('/<i class="iconify tabler--x[^>]*aria-hidden="true"/');
});

it('associa o label ao input por id e marca required (regressão)', function (): void {
    $html = Blade::render('<x-shared.file-upload name="logo" label="Logo da empresa" :required="true" />');

    expect($html)
        ->toContain('Logo da empresa')
        ->toContain('for="logo"')
        ->toContain('id="logo"');
});

it('modo dropzone: a zona vive sob wire:ignore e o input fica fora dele', function (): void {
    $html = Blade::render('<x-shared.file-upload name="planilha" mode="dropzone" />');

    // O morph do Livewire apagaria os chips do Dropzone; a zona é protegida por
    // wire:ignore e o input permanece fora para o wire:model re-bindar no morph.
    $posicaoInput = strpos($html, 'data-af-dropzone-input');
    $posicaoIgnore = strpos($html, 'wire:ignore');

    expect($posicaoIgnore)->not->toBeFalse()
        ->and($posicaoInput)->toBeLessThan($posicaoIgnore);
});

it('modo dropzone: aceita textos personalizados da zona via title e description', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-shared.file-upload
            name="planilha"
            mode="dropzone"
            title="Arraste a planilha aqui"
            description="Somente .xlsx até 20MB."
        />
        BLADE);

    expect($html)
        ->toContain('Arraste a planilha aqui')
        ->toContain('Somente .xlsx até 20MB.')
        ->not->toContain('Arraste arquivos aqui ou clique para enviar');
});

it('modo dropzone: renderiza a barra de progresso de upload por padrão e permite desligar', function (): void {
    $comBarra = Blade::render('<x-shared.file-upload name="planilha" mode="dropzone" />');
    $semBarra = Blade::render('<x-shared.file-upload name="planilha" mode="dropzone" :progress="false" />');

    expect($comBarra)
        ->toContain('x-on:livewire-upload-progress')
        ->toContain('x-if="enviando"')
        ->and($semBarra)->not->toContain('livewire-upload-progress');
});

it('modo dropzone: chip nasce com ícone genérico e imagem escondida (não-imagens não têm thumbnail)', function (): void {
    $html = Blade::render('<x-shared.file-upload name="anexos" mode="dropzone" :multiple="true" />');

    expect($html)
        ->toContain('data-af-dz-icon')
        ->toContain('data-dz-errormessage')
        ->toMatch('/<img class="hidden[^>]*data-dz-thumbnail/');
});

it('modo dropzone: o tamanho padrão md permanece intacto e o lg vira zona hero', function (): void {
    $md = Blade::render('<x-shared.file-upload name="planilha" mode="dropzone" />');
    $lg = Blade::render('<x-shared.file-upload name="planilha" mode="dropzone" size="lg" />');

    expect($md)
        ->toContain('min-h-37.5')
        ->toContain('p-5')
        ->not->toContain('min-h-64')
        ->and($lg)->toContain('min-h-64')
        ->toContain('p-8')
        ->not->toContain('min-h-37.5');
});

it('modo dropzone: o ícone da zona é marcado para o drag state e elevação no hover', function (): void {
    $html = Blade::render('<x-shared.file-upload name="planilha" mode="dropzone" />');

    // O CSS de plugins/_dropzone.css eleva o ícone via [data-af-dz-zone-icon]
    // quando o Dropzone toggla dz-drag-hover; o hover usa group-hover.
    expect($html)
        ->toContain('data-af-dz-zone-icon')
        ->toContain('group-hover:-translate-y-0.5');
});
