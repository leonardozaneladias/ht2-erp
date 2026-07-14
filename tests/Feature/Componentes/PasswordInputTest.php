<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// Acessibilidade — o ícone do botão de mostrar/ocultar senha (tabler--eye, alvo do JS
// data-password-toggle-icon) é puramente decorativo: o BOTÃO já tem nome acessível via
// aria-label="Mostrar senha", logo o <i> só adicionaria ruído ao leitor de tela. Recebe
// aria-hidden="true" (mesma convenção de button/dropdown-item/kpi-card/file-upload/input/
// unsaved-bar). É a base de TODO campo de senha do ERP (login, perfil, usuários, setup,
// reset). O JS troca a classe eye/eye-off via classList — não recria o <i> nem toca em
// atributos — então o aria-hidden persiste ao alternar. Caracteriza também os contratos
// label↔input, hint/erro (aria-describedby), required e aria-invalid, até então sem teste.

it('o ícone do botão de toggle recebe aria-hidden', function (): void {
    $html = Blade::render('<x-shared.password-input name="senha" />');

    expect($html)
        ->toMatch('/<i class="iconify tabler--eye[^>]*aria-hidden="true"/')
        ->toContain('data-password-toggle-icon') // o JS de toggle continua encontrando o alvo
        ->toContain('aria-label="Mostrar senha"'); // o nome acessível segue no botão
});

it('associa o label ao input pelo id derivado do name e usa type=password (regressão)', function (): void {
    $html = Blade::render('<x-shared.password-input name="dados.senha" label="Nova senha" />');

    // o fieldId normaliza pontos/colchetes do name para um id de DOM válido
    expect($html)
        ->toContain('for="dados-senha"')
        ->toContain('id="dados-senha"')
        ->toContain('name="dados.senha"')
        ->toContain('type="password"')
        ->toContain('Nova senha');
});

it('marca required com indicador visual e aria-invalid false por padrão (regressão)', function (): void {
    $html = Blade::render('<x-shared.password-input name="senha" :required="true" />');

    expect($html)
        ->toContain('required')
        ->toContain('aria-invalid="false"')
        ->toContain('text-danger'); // x-shared.required-indicator (asterisco)
});

it('hint é associado ao input via aria-describedby (regressão)', function (): void {
    $html = Blade::render('<x-shared.password-input name="senha" hint="Mínimo de 8 caracteres" />');

    expect($html)
        ->toContain('id="senha-hint"')
        ->toContain('aria-describedby="senha-hint"')
        ->toContain('Mínimo de 8 caracteres');
});

it('sem withMeter não renderiza o medidor de força (regressão)', function (): void {
    $html = Blade::render('<x-shared.password-input name="senha" />');

    expect($html)->not->toContain('data-password-meter-bar');
});
