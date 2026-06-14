<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusProduto: string
{
    public function label(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho',
            self::Publicado => 'Publicado',
            self::Arquivado => 'Arquivado',
        };
    }

    /**
     * Variante visual do badge de status (x-shared.badge).
     */
    public function variant(): string
    {
        return match ($this) {
            self::Rascunho => 'warning',
            self::Publicado => 'success',
            self::Arquivado => 'default',
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

    case Rascunho = 'rascunho';
    case Publicado = 'publicado';
    case Arquivado = 'arquivado';
}
