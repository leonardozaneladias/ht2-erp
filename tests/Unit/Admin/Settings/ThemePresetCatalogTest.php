<?php

declare(strict_types=1);

use App\Enums\Admin\Appearance\MenuColor;
use App\Enums\Admin\Appearance\Skin;
use App\Enums\Admin\Appearance\TemaPadrao;
use App\Enums\Admin\Appearance\ThemePreset;
use App\Enums\Admin\Appearance\TopbarColor;
use App\Services\Admin\Settings\ThemePresetCatalog;

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

it('o preset padrão HT2 ERP usa a paleta de fábrica', function () {
    $dto = (new ThemePresetCatalog)->paraEnum(ThemePreset::HT2_ERP);

    expect($dto->cor_primaria)->toBe('#1577ce')
        ->and($dto->cor_info)->toBe('#36a8ff')
        ->and($dto->tema_padrao)->toBe('light');
});

it('todos() devolve um preset por case do enum', function () {
    expect((new ThemePresetCatalog)->todos())->toHaveCount(count(ThemePreset::cases()));
});
