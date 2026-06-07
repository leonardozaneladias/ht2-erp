<?php

declare(strict_types=1);

use App\Enums\TipoNotificacao;
use App\Livewire\Admin\Notificacoes\MinhasNotificacoes;
use App\Notifications\ComunicadoNotification;
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
