<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums\Admin\Appearance;

/**
 * Tamanho padrão do menu lateral (atributo data-sidenav-size do <html>).
 */
enum SidenavSize: string
{
    public function label(): string
    {
        return match ($this) {
            self::DEFAULT => 'Padrão',
            self::COMPACT => 'Compacto',
            self::CONDENSED => 'Condensado (ícones)',
            self::ON_HOVER => 'Expandir ao passar o mouse',
            self::OFFCANVAS => 'Oculto (off-canvas)',
        };
    }

    public static function padrao(): self
    {
        return self::DEFAULT;
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
    case DEFAULT = 'default';
    case COMPACT = 'compact';
    case CONDENSED = 'condensed';
    case ON_HOVER = 'on-hover';
    case OFFCANVAS = 'offcanvas';
}
