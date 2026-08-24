<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Acesso\PainelPessoa;
use HT2ML\Core\Livewire\Admin\Impersonation\IniciarImpersonation;
use HT2ML\Core\Livewire\Admin\Usuarios\UsuariosTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = criarAdminUser('super@teste.com');
    $this->admin->assignRole('super-admin');
});

it('tabela de usuários mostra a foto quando há avatar e iniciais quando não há', function () {
    $comFoto = criarAdminUser('comfoto@teste.com');
    $comFoto->forceFill(['nome' => 'Ana Silva', 'avatar_url' => 'avatars/ana.jpg'])->save();

    $semFoto = criarAdminUser('semfoto@teste.com');
    $semFoto->forceFill(['nome' => 'Bruno Costa'])->save();

    $html = Livewire::actingAs($this->admin, 'admin')
        ->test(UsuariosTable::class)
        ->html();

    expect($html)
        ->toContain('avatars/ana.jpg')
        ->toContain('BC'); // iniciais de Bruno Costa
});

it('painel da pessoa no hub de acesso carrega o avatar do usuário', function () {
    $alvo = criarAdminUser('pessoa@teste.com');
    $alvo->forceFill(['avatar_url' => 'avatars/pessoa.jpg'])->save();

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPessoa::class, ['usuarioId' => $alvo->id])
        ->assertSet('avatarUrl', $alvo->fresh()->urlAvatar())
        ->assertSee('avatars/pessoa.jpg');
});

it('modal de impersonation mostra o avatar do alvo e limpa ao fechar', function () {
    $alvo = criarAdminUser('imp@teste.com');
    $alvo->forceFill(['avatar_url' => 'avatars/imp.jpg'])->save();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IniciarImpersonation::class)
        ->dispatch('impersonation::abrir', id: $alvo->id)
        ->assertSet('alvoAvatarUrl', $alvo->fresh()->urlAvatar())
        ->assertSee('avatars/imp.jpg')
        ->call('fechar')
        ->assertSet('alvoAvatarUrl', null);
});
