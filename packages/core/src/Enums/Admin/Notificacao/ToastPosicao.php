<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums\Admin\Notificacao;

/**
 * Posição em que as notificações (toasts) aparecem na tela.
 * O valor casa com o mapa de posições do motor JS (resources/js/admin/toast.js).
 */
enum ToastPosicao: string
{
    public function label(): string
    {
        return match ($this) {
            self::TOP_CENTER => 'Superior centralizado',
            self::TOP_RIGHT => 'Superior direito',
            self::TOP_LEFT => 'Superior esquerdo',
            self::BOTTOM_CENTER => 'Inferior centralizado',
            self::BOTTOM_RIGHT => 'Inferior direito',
            self::BOTTOM_LEFT => 'Inferior esquerdo',
        };
    }

    public static function padrao(): self
    {
        return self::TOP_CENTER;
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
    case TOP_CENTER = 'top-center';
    case TOP_RIGHT = 'top-right';
    case TOP_LEFT = 'top-left';
    case BOTTOM_CENTER = 'bottom-center';
    case BOTTOM_RIGHT = 'bottom-right';
    case BOTTOM_LEFT = 'bottom-left';
}
