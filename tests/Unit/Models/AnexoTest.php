<?php

declare(strict_types=1);

use HT2ML\Core\Models\Anexo;
use HT2ML\Core\Models\Concerns\Anexavel;

test('Anexo formata tamanho em bytes', function () {
    $anexo = new Anexo(['tamanho' => 500]);
    expect($anexo->tamanhoFormatado())->toBe('500 B');
});

test('Anexo formata tamanho em KB', function () {
    $anexo = new Anexo(['tamanho' => 2048]);
    expect($anexo->tamanhoFormatado())->toBe('2 KB');
});

test('Anexo formata tamanho em MB', function () {
    $anexo = new Anexo(['tamanho' => 1_048_576]);
    expect($anexo->tamanhoFormatado())->toBe('1 MB');
});

test('trait Anexavel declara método anexos', function () {
    expect(method_exists(Anexavel::class, 'anexos'))->toBeTrue();
});
