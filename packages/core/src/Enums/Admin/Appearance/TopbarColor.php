<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums\Admin\Appearance;

/**
 * Cor da barra superior (atributo data-topbar-color do <html>).
 */
enum TopbarColor: string
{
    public function label(): string
    {
        return match ($this) {
            self::LIGHT => 'Clara',
            self::DARK => 'Escura',
            self::GRAY => 'Cinza',
            self::GRADIENT => 'Gradiente',
        };
    }

    /** Amostra visual para o seletor (cor sólida ou gradiente CSS). */
    public function swatch(): string
    {
        return match ($this) {
            self::LIGHT => '#ffffff',
            self::DARK => '#252630',
            self::GRAY => '#f1f2f7',
            self::GRADIENT => 'linear-gradient(135deg, #1a455f, #262549)',
        };
    }

    public static function padrao(): self
    {
        return self::LIGHT;
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
}
