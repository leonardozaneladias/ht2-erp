<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums;

enum StatusAdminUser: string
{
    public function label(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::INATIVO => 'Inativo',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::ATIVO => 'success',
            self::INATIVO => 'neutral',
        };
    }

    public static function fromBoolean(bool $ativo): self
    {
        return $ativo ? self::ATIVO : self::INATIVO;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
    case ATIVO = 'ativo';
    case INATIVO = 'inativo';
}
