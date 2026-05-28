<?php

declare(strict_types=1);

it('adiciona X-Request-Id a toda resposta da API', function (): void {
    $response = $this->getJson('/api/v1/me');

    $response->assertHeader('X-Request-Id');
    expect($response->headers->get('X-Request-Id'))->not->toBeEmpty();
});

it('X-Request-Id é um UUID v4 válido', function (): void {
    $response = $this->getJson('/api/v1/me');

    $requestId = $response->headers->get('X-Request-Id');

    expect($requestId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
});

it('cada requisição gera um X-Request-Id único', function (): void {
    $first = $this->getJson('/api/v1/me')->headers->get('X-Request-Id');
    $second = $this->getJson('/api/v1/me')->headers->get('X-Request-Id');

    expect($first)->not->toBe($second);
});
