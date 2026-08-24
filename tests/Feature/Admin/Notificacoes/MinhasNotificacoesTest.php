<?php

declare(strict_types=1);

use App\Livewire\Admin\Notificacoes\MinhasNotificacoes;
use HT2ML\Core\Enums\TipoNotificacao;
use HT2ML\Core\Notifications\ComunicadoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lista todas as notificações e filtra as não lidas', function (): void {
    $user = criarAdminUser('central@teste.com');
    $user->notify(new ComunicadoNotification(TipoNotificacao::Info, 'Já lida', 'a'));
    $user->notify(new ComunicadoNotification(TipoNotificacao::Aviso, 'Pendente', 'b'));

    $jaLida = $user->notifications()->get()->first(fn ($n): bool => $n->data['titulo'] === 'Já lida');
    $jaLida->markAsRead();

    $this->actingAs($user, 'admin');

    Livewire::test(MinhasNotificacoes::class)
        ->assertSee('Já lida')
        ->assertSee('Pendente')
        ->call('definirFiltro', 'nao_lidas')
        ->assertSee('Pendente')
        ->assertDontSee('Já lida');
});

it('marca uma notificação como lida', function (): void {
    $user = criarAdminUser('central@teste.com');
    $user->notify(new ComunicadoNotification(TipoNotificacao::Info, 'X', 'x'));
    $id = (string) $user->notifications()->first()->id;
    $this->actingAs($user, 'admin');

    Livewire::test(MinhasNotificacoes::class)->call('marcarComoLida', $id);

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('exclui uma notificação', function (): void {
    $user = criarAdminUser('central@teste.com');
    $user->notify(new ComunicadoNotification(TipoNotificacao::Info, 'X', 'x'));
    $id = (string) $user->notifications()->first()->id;
    $this->actingAs($user, 'admin');

    Livewire::test(MinhasNotificacoes::class)->call('excluir', $id);

    expect($user->fresh()->notifications()->count())->toBe(0);
});

// Regressão da paginação Livewire (WithPagination): com mais de uma página a
// barra é renderizada e os links de página expõem o aria-label traduzido para
// PT-BR ("Ir para a página N"). Os demais casos criam poucos itens (1 página),
// e a barra só aparece quando hasPages() é true — por isso este caso é
// necessário para guardar a tradução PT-BR (CLAUDE.md §4) na tela real.
it('renderiza a barra de paginação com aria-label em PT-BR quando há mais de uma página', function (): void {
    $user = criarAdminUser('paginacao@teste.com');

    foreach (range(1, 16) as $i) {
        $user->notify(new ComunicadoNotification(TipoNotificacao::Info, "Notificação {$i}", "corpo {$i}"));
    }

    $this->actingAs($user, 'admin');

    Livewire::test(MinhasNotificacoes::class)
        ->assertOk()
        ->assertSeeHtml('aria-label="Ir para a página')
        ->assertDontSeeHtml('aria-label="Go to page');
});
