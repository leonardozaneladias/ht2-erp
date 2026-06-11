<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aplica os headers de segurança nas respostas web', function () {
    $response = $this->get('/admin/login');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    expect($response->headers->has('Content-Security-Policy-Report-Only'))->toBeTrue();
});

it('só envia HSTS em conexões seguras', function () {
    $inseguro = $this->get('http://localhost/admin/login');
    expect($inseguro->headers->has('Strict-Transport-Security'))->toBeFalse();

    $seguro = $this->get('https://localhost/admin/login');
    expect($seguro->headers->has('Strict-Transport-Security'))->toBeTrue();
});
