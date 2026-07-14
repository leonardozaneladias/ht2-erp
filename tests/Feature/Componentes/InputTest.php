<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// Acessibilidade — o ícone opcional do x-shared.input (prop `icon`) é puramente
// decorativo: o nome do campo vem SEMPRE do <label for>, nunca do <i>. Logo recebe
// aria-hidden="true" (mesma convenção de button/dropdown-item/kpi-card/file-upload/
// unsaved-bar). Este é o componente de maior alavancagem da base de formulários: além
// do uso direto, é a fundação de cep/cnpj/cpf/phone/money-input — todos herdam a
// correção. Caracteriza também os contratos de associação label↔input, hint/erro
// (aria-describedby) e required, até então sem teste.

it('o ícone decorativo do campo recebe aria-hidden', function (): void {
    $html = Blade::render('<x-shared.input name="email" label="E-mail" icon="tabler--mail" />');

    expect($html)
        ->toMatch('/<i class="iconify tabler--mail input-icon"[^>]*aria-hidden="true"/')
        ->toContain('E-mail') // nome do campo segue no texto do <label>
        ->toContain('for="email"');
});

it('sem a prop icon não renderiza ícone nem o grupo de ícone (regressão)', function (): void {
    $html = Blade::render('<x-shared.input name="nome" label="Nome" />');

    expect($html)
        ->not->toContain('input-icon-group')
        ->not->toContain('class="iconify')
        ->toContain('id="nome"')
        ->toContain('for="nome"');
});

it('associa o label ao input pelo id derivado do name (regressão)', function (): void {
    $html = Blade::render('<x-shared.input name="dados.cnpj" label="CNPJ" />');

    // o fieldId normaliza pontos/colchetes do name para um id de DOM válido
    expect($html)
        ->toContain('for="dados-cnpj"')
        ->toContain('id="dados-cnpj"')
        ->toContain('name="dados.cnpj"');
});

it('marca required com indicador visual e aria-invalid false por padrão (regressão)', function (): void {
    $html = Blade::render('<x-shared.input name="cpf" label="CPF" :required="true" />');

    expect($html)
        ->toContain('required')
        ->toContain('aria-invalid="false"')
        ->toContain('text-danger'); // x-shared.required-indicator (asterisco)
});

it('hint é associado ao input via aria-describedby (regressão)', function (): void {
    $html = Blade::render('<x-shared.input name="apelido" label="Apelido" hint="Como prefere ser chamado" />');

    expect($html)
        ->toContain('id="apelido-hint"')
        ->toContain('aria-describedby="apelido-hint"')
        ->toContain('Como prefere ser chamado');
});
