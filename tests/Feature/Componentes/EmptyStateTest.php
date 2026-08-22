<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('usa h3 como nível de heading padrão (compatibilidade)', function (): void {
    $html = Blade::render('<x-shared.empty-state title="Sem registros" />');

    // Default preservado: os usos existentes (standalone) continuam em <h3>.
    expect($html)
        ->toContain('<h3')
        ->toContain('Sem registros')
        ->toContain('</h3>');
});

it('permite configurar o nível do heading via title-level', function (): void {
    $html = Blade::render('<x-shared.empty-state title="Sem registros" title-level="h5" />');

    // Sob um page-header (<h4>), o empty-state deve descer para <h5> e não
    // regredir para <h3> — caso contrário a árvore de headings fica h4 -> h3.
    expect($html)
        ->toContain('<h5')
        ->toContain('</h5>')
        ->not->toContain('<h3');
});

it('cai no h3 quando o nível informado é inválido (segurança)', function (): void {
    $html = Blade::render('<x-shared.empty-state title="Sem registros" title-level="span onmouseover=alert(1)" />');

    // Whitelist h1..h6: qualquer valor fora da lista volta ao default h3,
    // impedindo injeção de tag arbitrária na interpolação <{{ $titleLevel }}>.
    expect($html)
        ->toContain('<h3')
        ->not->toContain('onmouseover');
});

it('preserva as classes de tipografia do título ao trocar o nível', function (): void {
    $html = Blade::render('<x-shared.empty-state title="Sem registros" title-level="h5" size="sm" />');

    // Trocar a tag não pode alterar o tamanho visual: as classes Tailwind do
    // título (vindas de $config['title']) seguem aplicadas no novo heading.
    expect($html)
        ->toContain('font-semibold')
        ->toContain('text-base'); // size="sm" => title text-base
});

it('marca o ícone decorativo como aria-hidden (a11y)', function (): void {
    $html = Blade::render('<x-shared.empty-state title="Nenhum registro encontrado" icon="tabler--inbox" />');

    // O ícone do estado vazio é puramente decorativo: o título (heading) já
    // comunica o estado. Esconder do leitor de tela evita ruído redundante.
    // Cobertura total: todo <i class="iconify ..."> precisa de aria-hidden.
    expect($html)->toContain('aria-hidden="true"');

    preg_match_all('/<i\b[^>]*\bclass="[^"]*\biconify\b[^"]*"[^>]*>/', $html, $icones);
    expect($icones[0])->not->toBeEmpty();
    foreach ($icones[0] as $icone) {
        expect($icone)->toContain('aria-hidden="true"');
    }
});
