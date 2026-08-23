<?php

declare(strict_types=1);

use HT2ML\Core\Support\Html\HtmlSanitizer;

it('mantém formatação permitida e remove conteúdo perigoso', function (): void {
    $sujo = '<p>Olá <strong>time</strong> <em>já</em></p>'
        . '<script>alert(1)</script>'
        . '<img src="x" onerror="evil()">'
        . '<a href="https://exemplo.com" onclick="x()">link</a>';

    $limpo = HtmlSanitizer::clean($sujo);

    expect($limpo)
        ->toContain('<strong>time</strong>')
        ->toContain('<em>já</em>')
        ->toContain('href="https://exemplo.com"')
        ->not->toContain('script')
        ->not->toContain('onerror')
        ->not->toContain('onclick')
        ->not->toContain('<img');
});

it('é idempotente (limpar duas vezes não muda)', function (): void {
    $uma = HtmlSanitizer::clean('<p>oi <b>bold</b></p>');

    expect(HtmlSanitizer::clean($uma))->toBe($uma);
});

it('retorna string vazia para entrada vazia', function (): void {
    expect(HtmlSanitizer::clean(''))->toBe('')
        ->and(HtmlSanitizer::clean('   '))->toBe('');
});
