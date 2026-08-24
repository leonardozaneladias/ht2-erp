<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Admin\Auditoria\IndexAuditoria;
use HT2ML\Core\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('gestor', 50)->givePermissionTo('auditoria.visualizar');
});

it('super-admin expurga logs pelo botão', function (): void {
    config(['activitylog.clean_after_days' => 30]);
    $antiga = activity('test')->log('antiga');
    Activity::query()->whereKey($antiga->id)->update(['created_at' => now()->subDays(60)]);

    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');
    $this->actingAs($super, 'admin');

    Livewire::test(IndexAuditoria::class)->call('expurgar');

    expect(Activity::query()->whereKey($antiga->id)->exists())->toBeFalse();
});

it('não-super-admin não expurga', function (): void {
    $gestor = criarAdminUser('gestor@teste.com');
    $gestor->assignRole('gestor');
    $this->actingAs($gestor, 'admin');

    Livewire::test(IndexAuditoria::class)->call('expurgar')->assertForbidden();
});
