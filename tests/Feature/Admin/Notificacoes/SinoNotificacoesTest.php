<?php

declare(strict_types=1);

use App\Enums\TipoNotificacao;
use App\Livewire\Admin\Notificacoes\SinoNotificacoes;
use App\Notifications\ComunicadoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('mostra as recentes e conta as não lidas', function (): void {
    $user = criarAdminUser('sino@teste.com');
    $user->notify(new ComunicadoNotification(TipoNotificacao::Info, 'Aviso geral', 'Corpo do aviso.'));
    $this->actingAs($user, 'admin');

    Livewire::test(SinoNotificacoes::class)->assertSee('Aviso geral');

    expect($user->unreadNotifications()->count())->toBe(1);
});

it('abrir marca a notificação como lida', function (): void {
    $user = criarAdminUser('sino@teste.com');
    $user->notify(new ComunicadoNotification(TipoNotificacao::Info, 'Olá', 'Corpo.'));
    $id = (string) $user->notifications()->first()->id;
    $this->actingAs($user, 'admin');

    Livewire::test(SinoNotificacoes::class)->call('abrir', $id);

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('marca todas como lidas', function (): void {
    $user = criarAdminUser('sino@teste.com');
    $user->notify(new ComunicadoNotification(TipoNotificacao::Info, 'A', 'a'));
    $user->notify(new ComunicadoNotification(TipoNotificacao::Aviso, 'B', 'b'));
    $this->actingAs($user, 'admin');

    Livewire::test(SinoNotificacoes::class)->call('marcarTodasComoLidas');

    expect($user->unreadNotifications()->count())->toBe(0);
});
