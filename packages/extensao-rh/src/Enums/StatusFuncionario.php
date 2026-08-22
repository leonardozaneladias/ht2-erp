<?php

declare(strict_types=1);

namespace HT2ML\Rh\Enums;

enum StatusFuncionario: string
{
    public function label(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Afastado => 'Afastado',
            self::Desligado => 'Desligado',
        };
    }

    /**
     * Variante visual do badge de status (x-shared.badge).
     */
    public function variant(): string
    {
        return match ($this) {
            self::Ativo => 'success',
            self::Afastado => 'default',
            self::Desligado => 'default',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso): array => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }

    case Ativo = 'ativo';
    case Afastado = 'afastado';
    case Desligado = 'desligado';
}
