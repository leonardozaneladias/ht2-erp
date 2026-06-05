<?php

declare(strict_types=1);

use App\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('expõe os settings de endurecimento com defaults', function (): void {
    $s = app(SegurancaSettings::class);
    expect($s->login_max_tentativas)->toBe(5)
        ->and($s->login_janela_minutos)->toBe(1)
        ->and($s->lockout_max_falhas)->toBe(10)
        ->and($s->lockout_duracao_minutos)->toBe(15)
        ->and($s->alertas_seguranca_habilitados)->toBeTrue()
        ->and($s->alerta_login_super_admin)->toBeFalse();
});
