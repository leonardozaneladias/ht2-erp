<?php

declare(strict_types=1);

use App\Livewire\Admin\Conta\TrocarSenha;
use HT2ML\Core\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('troca a senha com a senha atual correta', function (): void {
    $user = criarAdminUser('senha@teste.com'); // senha = "password"
    $this->actingAs($user, 'admin');

    Livewire::test(TrocarSenha::class)
        ->set('senhaAtual', 'password')
        ->set('novaSenha', 'NovaSenha9')
        ->set('novaSenha_confirmation', 'NovaSenha9')
        ->call('trocar')
        ->assertHasNoErrors();

    expect(Hash::check('NovaSenha9', $user->fresh()->password))->toBeTrue()
        ->and(Activity::where('log_name', 'conta')->where('event', 'senha_alterada')->exists())->toBeTrue();
});

it('rejeita a senha atual incorreta', function (): void {
    $user = criarAdminUser('senha@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(TrocarSenha::class)
        ->set('senhaAtual', 'errada')
        ->set('novaSenha', 'NovaSenha9')
        ->set('novaSenha_confirmation', 'NovaSenha9')
        ->call('trocar')
        ->assertHasErrors('senhaAtual');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('rejeita nova senha fraca', function (): void {
    $user = criarAdminUser('senha@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(TrocarSenha::class)
        ->set('senhaAtual', 'password')
        ->set('novaSenha', 'fraca')
        ->set('novaSenha_confirmation', 'fraca')
        ->call('trocar')
        ->assertHasErrors('novaSenha');
});
