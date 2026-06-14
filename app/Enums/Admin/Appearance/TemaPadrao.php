<?php

declare(strict_types=1);

namespace App\Enums\Admin\Appearance;

/**
 * Tema padrão da instância (atributo data-theme do <html>).
 */
enum TemaPadrao: string
{
    public function label(): string
    {
        return match ($this) {
            self::LIGHT => 'Claro',
            self::DARK => 'Escuro',
            self::SYSTEM => 'Sistema',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LIGHT => 'tabler--sun',
            self::DARK => 'tabler--moon',
            self::SYSTEM => 'tabler--device-desktop',
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
    case SYSTEM = 'system';
}
