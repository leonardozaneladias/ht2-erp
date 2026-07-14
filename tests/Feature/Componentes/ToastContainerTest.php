<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// O toast-container vive em todo layout do ERP (admin + setup). Os <template>
// pílula/card são clonados em runtime pelo resources/js/admin/toast.js, que
// preenche conteúdo e — para erros/avisos — eleva a live region para assertiva.
// Estes testes fixam o CONTRATO BASE de acessibilidade declarado na Blade (o
// padrão polite/status sobre o qual o JS opera). A elevação assertiva por
// severidade ocorre em runtime no JS e é verificada no Browser (ver progresso.md).

it('renderiza o container com os dois templates de estilo (pílula e card)', function (): void {
    $html = Blade::render('<x-shared.toast-container />');

    expect($html)
        ->toContain('data-toast-container')
        ->toContain('data-toast-template="pilula"')
        ->toContain('data-toast-template="card"');
});

it('declara live region polite (role="status"/aria-live="polite") como padrão em cada template', function (): void {
    $html = Blade::render('<x-shared.toast-container />');

    // Cada item de toast (um por template) é a base que o JS clona: precisa nascer
    // como live region polite, para que sucesso/info sejam anunciados sem
    // interromper e o JS só precise elevar erros/avisos.
    expect(substr_count($html, 'data-toast-item'))->toBe(2);
    expect(substr_count($html, 'role="status"'))->toBe(2);
    expect(substr_count($html, 'aria-live="polite"'))->toBe(2);
});

it('mantém o gancho de ícone/título/mensagem que o JS preenche em runtime', function (): void {
    $html = Blade::render('<x-shared.toast-container />');

    // Regressão dos data-attributes consumidos por toast.js::buildToast.
    expect($html)
        ->toContain('data-toast-icon')
        ->toContain('data-toast-title')
        ->toContain('data-toast-message')
        ->toContain('data-toast-close');
});

it('marca todos os ícones decorativos dos toasts como aria-hidden', function (): void {
    $html = Blade::render('<x-shared.toast-container />');

    // 4 ícones puramente visuais (2 por template): o ícone de TIPO (data-toast-icon —
    // toast.js só lhe adiciona classes, nunca texto/nome acessível; o tipo é comunicado
    // pela mensagem e pela elevação de role/aria-live) e o "x" do botão fechar (que já
    // tem aria-label="Fechar"). Nenhum carrega informação para o leitor de tela.
    expect(substr_count($html, 'aria-hidden="true"'))->toBe(4);

    // Cobertura total: nenhum <i> iconify pode restar sem aria-hidden.
    preg_match_all('/<i\b[^>]*iconify[^>]*>/', $html, $todos);
    preg_match_all('/<i\b[^>]*iconify[^>]*aria-hidden="true"[^>]*>/', $html, $comHidden);
    expect($todos[0])->toHaveCount(4);
    expect($comHidden[0])->toHaveCount(count($todos[0]));
});
