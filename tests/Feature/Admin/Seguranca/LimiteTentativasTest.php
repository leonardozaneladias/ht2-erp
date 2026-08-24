<?php

declare(strict_types=1);

use HT2ML\Core\Services\Admin\Security\LimiteTentativas;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('excede após o máximo configurado e limpa', function (): void {
    $s = app(SegurancaSettings::class);
    $s->login_max_tentativas = 2;
    $s->save();

    $lim = app(LimiteTentativas::class);
    $chave = 'teste:x';

    expect($lim->excedido($chave))->toBeFalse();
    $lim->registrar($chave);
    $lim->registrar($chave);
    expect($lim->excedido($chave))->toBeTrue();

    $lim->limpar($chave);
    expect($lim->excedido($chave))->toBeFalse();
});
