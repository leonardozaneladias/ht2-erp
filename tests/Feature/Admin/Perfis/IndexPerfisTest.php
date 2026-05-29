<?php

declare(strict_types=1);

use App\Livewire\Admin\Perfis\IndexPerfis;
use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

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

it('lista perfis existentes', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexPerfis::class)
        ->assertOk()
        ->assertSee('super-admin')
        ->assertSee('gestor');
});
