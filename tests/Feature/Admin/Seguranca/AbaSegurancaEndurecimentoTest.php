<?php

declare(strict_types=1);

use App\Livewire\Admin\Configuracao\AbaSeguranca;
use App\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('salva os settings de endurecimento pela aba', function (): void {
    Livewire::test(AbaSeguranca::class)
        ->set('lockout_max_falhas', 7)
        ->set('alerta_login_super_admin', true)
        ->call('salvar');

    $s = app(SegurancaSettings::class);
    expect($s->lockout_max_falhas)->toBe(7)
        ->and($s->alerta_login_super_admin)->toBeTrue();
});
