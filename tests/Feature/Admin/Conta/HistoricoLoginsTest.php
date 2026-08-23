<?php

declare(strict_types=1);

use App\Livewire\Admin\Conta\HistoricoLogins;
use HT2ML\Core\Models\LoginHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lista os logins do próprio usuário', function (): void {
    $user = criarAdminUser('hist@teste.com');
    LoginHistory::create(['admin_user_id' => $user->id, 'ip_address' => '198.51.100.9', 'user_agent' => 'Firefox']);
    $this->actingAs($user, 'admin');

    Livewire::withoutLazyLoading()->test(HistoricoLogins::class)
        ->assertOk()
        ->assertSee('198.51.100.9');
});

it('não mostra logins de outro usuário', function (): void {
    $user = criarAdminUser('hist@teste.com');
    $outro = criarAdminUser('outro@teste.com');
    LoginHistory::create(['admin_user_id' => $outro->id, 'ip_address' => '203.0.113.50', 'user_agent' => 'Chrome']);
    $this->actingAs($user, 'admin');

    Livewire::withoutLazyLoading()->test(HistoricoLogins::class)->assertDontSee('203.0.113.50');
});
