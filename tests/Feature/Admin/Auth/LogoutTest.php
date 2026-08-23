<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('faz logout e redireciona para login', function () {
    $admin = AdminUser::create([
        'nome' => 'Admin Teste',
        'email' => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    expect(auth('admin')->check())->toBeFalse();
});
