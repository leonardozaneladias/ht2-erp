<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('usa role="alert" (assertivo) para erros e avisos', function (string $variant): void {
    $html = Blade::render('<x-shared.alert :variant="$variant">Mensagem</x-shared.alert>', [
        'variant' => $variant,
    ]);

    // Erros e avisos exigem atenção imediata: a live region assertiva (role="alert")
    // interrompe o leitor de tela para anunciar a mensagem.
    expect($html)->toContain('role="alert"');
})->with(['danger', 'warning']);

it('usa role="status" (polite) para confirmações e mensagens informativas', function (string $variant): void {
    $html = Blade::render('<x-shared.alert :variant="$variant">Mensagem</x-shared.alert>', [
        'variant' => $variant,
    ]);

    // Confirmações/infos não devem interromper o usuário: role="status" anuncia de
    // forma polite, alinhado ao x-shared.toast. Não deve carimbar role="alert".
    expect($html)
        ->toContain('role="status"')
        ->not->toContain('role="alert"');
})->with(['success', 'info', 'primary', 'secondary', 'light', 'dark']);

it('cai em role="status" para variant inválido (default info)', function (): void {
    $html = Blade::render('<x-shared.alert variant="inexistente">Mensagem</x-shared.alert>');

    expect($html)->toContain('role="status"');
});

it('preserva o role explícito passado pelo desenvolvedor (compatibilidade)', function (): void {
    // O merge mantém atributos explícitos: um role="..." passado na tag deve
    // sobrescrever o default por severidade, sem regressão de API.
    $html = Blade::render('<x-shared.alert variant="success" role="alert">Mensagem</x-shared.alert>');

    expect($html)
        ->toContain('role="alert"')
        ->not->toContain('role="status"');
});

it('marca o ícone de severidade como aria-hidden (decorativo)', function (): void {
    // A severidade já é comunicada por role (alert/status), cor e texto — mesmo
    // critério do x-shared.toast. O ícone (tabler--circle-check etc.) é reforço
    // visual redundante e não deve ser narrado pelo leitor de tela.
    $html = Blade::render('<x-shared.alert variant="success">Salvo</x-shared.alert>');

    preg_match_all('/<i\b[^>]*iconify[^>]*>/', $html, $todos);
    preg_match_all('/<i\b[^>]*iconify[^>]*aria-hidden="true"[^>]*>/', $html, $comHidden);

    expect($todos[0])->toHaveCount(1);
    expect($comHidden[0])->toHaveCount(1);
});

it('marca todos os ícones decorativos (severidade + botão fechar) como aria-hidden', function (): void {
    // danger traz o ícone de severidade (tabler--alert-octagon); dismissible adiciona
    // o botão fechar (tabler--x), que já tem aria-label="Fechar". Ambos os <i iconify>
    // são decorativos: cobertura total exigida.
    $html = Blade::render('<x-shared.alert variant="danger" :dismissible="true">Erro</x-shared.alert>');

    preg_match_all('/<i\b[^>]*iconify[^>]*>/', $html, $todos);
    preg_match_all('/<i\b[^>]*iconify[^>]*aria-hidden="true"[^>]*>/', $html, $comHidden);

    expect($todos[0])->toHaveCount(2);
    expect($comHidden[0])->toHaveCount(2);
});
