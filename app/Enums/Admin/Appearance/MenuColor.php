<?php

declare(strict_types=1);

namespace App\Enums\Admin\Appearance;

/**
 * Cor do menu lateral (atributo data-menu-color do <html>).
 */
enum MenuColor: string
{
    public function label(): string
    {
        return match ($this) {
            self::LIGHT => 'Claro',
            self::DARK => 'Escuro',
            self::GRAY => 'Cinza',
            self::GRADIENT => 'Gradiente',
            self::IMAGE => 'Imagem',
        };
    }

    /** Amostra visual para o seletor (cor sólida ou gradiente CSS). */
    public function swatch(): string
    {
        return match ($this) {
            self::LIGHT => '#ffffff',
            self::DARK => '#23303c',
            self::GRAY => '#f1f2f7',
            self::GRADIENT => 'linear-gradient(135deg, #1a455f, #262549)',
            self::IMAGE => 'linear-gradient(135deg, #1a455f, #262549)',
        };
    }

    public static function padrao(): self
    {
        return self::DARK;
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $c): array => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }

    /** @return array<int, string> */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
    case LIGHT = 'light';
    case DARK = 'dark';
    case GRAY = 'gray';
    case GRADIENT = 'gradient';
    case IMAGE = 'image';
}
