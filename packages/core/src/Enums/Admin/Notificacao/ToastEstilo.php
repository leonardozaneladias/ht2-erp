<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums\Admin\Notificacao;

/**
 * Estilo visual da notificação. O valor casa com o nome do <template>
 * correspondente no toast-container.blade.php.
 */
enum ToastEstilo: string
{
    public function label(): string
    {
        return match ($this) {
            self::PILULA => 'Pílula (compacta)',
            self::CARD => 'Card (com título)',
        };
    }

    public static function padrao(): self
    {
        return self::PILULA;
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
    case PILULA = 'pilula';
    case CARD = 'card';
}
