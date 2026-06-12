<?php

declare(strict_types=1);

use App\Actions\Admin\ToggleAdminUserStatusAction;
use App\Livewire\Admin\Usuarios\UsuariosTable;
use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = AdminUser::create([
        'nome' => 'Super',
        'email' => 'super@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('alterna status do usuário e grava activity log', function () {
    $alvo = AdminUser::create([
        'nome' => 'Alvo',
        'email' => 'alvo@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $alvo->assignRole('gestor');

    Livewire::actingAs($this->admin, 'admin')
        ->test(UsuariosTable::class)
        ->call('alternarStatus', $alvo->id)
        ->assertHasNoErrors();

    $alvo->refresh();
    expect($alvo->ativo)->toBeFalse();

    // A desativação entra na auditoria como diff de `ativo` (trait Auditavel).
    $log = Activity::where('log_name', 'admin_users')
        ->where('event', 'updated')
        ->where('subject_id', $alvo->id)
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and(data_get($log->attribute_changes, 'old.ativo'))->toBeTrue()
        ->and(data_get($log->attribute_changes, 'attributes.ativo'))->toBeFalse();
});

it('impede o admin de desativar a si mesmo', function () {
    $action = app(ToggleAdminUserStatusAction::class);
    $this->actingAs($this->admin, 'admin');

    expect(fn () => $action->execute($this->admin))
        ->toThrow(RuntimeException::class, 'Você não pode desativar a si mesmo.');
});
