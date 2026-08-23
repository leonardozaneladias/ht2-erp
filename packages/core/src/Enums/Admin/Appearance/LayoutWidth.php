<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums\Admin\Appearance;

/**
 * Largura do layout (atributo data-layout-width do <html>).
 */
enum LayoutWidth: string
{
    public function label(): string
    {
        return match ($this) {
            self::FLUID => 'Fluida',
            self::BOXED => 'Centralizada',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::FLUID => 'Ocupa toda a largura disponível da tela.',
            self::BOXED => 'Conteúdo com largura máxima e margens laterais.',
        };
    }

    public static function padrao(): self
    {
        return self::FLUID;
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
    case FLUID = 'fluid';
    case BOXED = 'boxed';
}
