<?php

declare(strict_types=1);

namespace App\Enums\Admin\Appearance;

/**
 * Skin visual do template (atributo data-skin do <html>).
 *
 * Somente skins com CSS no projeto (resources/css/config/_theme-*.css).
 * Não incluir skins sem arquivo: o preview/seleção ficaria sem efeito.
 */
enum Skin: string
{
    public function label(): string
    {
        return match ($this) {
            self::DEFAULT => 'Padrão',
            self::MINIMAL => 'Minimal',
            self::MODERN => 'Moderno',
            self::MATERIAL => 'Material',
            self::SAAS => 'SaaS',
            self::PIXEL => 'Pixel',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::DEFAULT => 'Equilibrado, com sombras e cantos suaves.',
            self::MINIMAL => 'Enxuto, com bordas finas e pouco contraste.',
            self::MODERN => 'Cantos arredondados e respiro generoso.',
            self::MATERIAL => 'Inspirado no Material Design, com elevação.',
            self::SAAS => 'Visual de produto SaaS, limpo e direto.',
            self::PIXEL => 'Cantos retos e traço marcado.',
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
    case MINIMAL = 'minimal';
    case MODERN = 'modern';
    case MATERIAL = 'material';
    case SAAS = 'saas';
    case PIXEL = 'pixel';
}
