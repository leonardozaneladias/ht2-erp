<?php

declare(strict_types=1);

use HT2ML\Core\Enums\Admin\Appearance\MenuColor;
use HT2ML\Core\Enums\Admin\Appearance\Skin;
use HT2ML\Core\Enums\Admin\Appearance\TemaPadrao;
use HT2ML\Core\Enums\Admin\Appearance\ThemePreset;
use HT2ML\Core\Enums\Admin\Appearance\TopbarColor;
use HT2ML\Core\Services\Admin\Settings\ThemePresetCatalog;

it('cada preset retorna um DTO com cores hex válidas e chrome de enum válido', function (ThemePreset $preset) {
    $dto = (new ThemePresetCatalog)->paraEnum($preset);

    foreach ($dto->cores() as $hex) {
        expect($hex)->toMatch('/^#[0-9a-f]{6}$/');
    }

    expect(Skin::valores())->toContain($dto->skin)
        ->and(TopbarColor::valores())->toContain($dto->topbar_color)
        ->and(MenuColor::valores())->toContain($dto->menu_color)
        ->and(TemaPadrao::valores())->toContain($dto->tema_padrao);
})->with(ThemePreset::cases());

it('o preset padrão Safira usa a paleta de fábrica', function () {
    $dto = (new ThemePresetCatalog)->paraEnum(ThemePreset::SAFIRA);

    expect($dto->cor_primaria)->toBe('#1577ce')
        ->and($dto->cor_info)->toBe('#36a8ff')
        ->and($dto->tema_padrao)->toBe('light');
});

it('todos() devolve um preset por case do enum', function () {
    expect((new ThemePresetCatalog)->todos())->toHaveCount(count(ThemePreset::cases()));
});
