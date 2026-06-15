<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('responde 200 e status ok quando banco e cache estão saudáveis', function () {
    $this->get('/healthz')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'checks' => ['database' => 'ok', 'cache' => 'ok'],
        ]);
});

it('é público — não exige autenticação nem sistema instalado', function () {
    $this->get('/healthz')->assertOk()->assertJsonPath('status', 'ok');
});
