<?php

declare(strict_types=1);

use App\Livewire\Admin\Conta\PreferenciasConta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('salva idioma e fuso do usuário', function (): void {
    $user = criarAdminUser('pref@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(PreferenciasConta::class)
        ->set('locale', 'en')
        ->set('timezone', 'UTC')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($user->fresh()->locale)->toBe('en')
        ->and($user->fresh()->timezone)->toBe('UTC');
});

it('rejeita idioma fora da lista', function (): void {
    $user = criarAdminUser('pref@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(PreferenciasConta::class)
        ->set('locale', 'xx')
        ->call('salvar')
        ->assertHasErrors('locale');
});
