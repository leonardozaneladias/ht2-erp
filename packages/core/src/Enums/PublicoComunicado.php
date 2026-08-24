<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums;

/**
 * Público-alvo de um comunicado in-app: todos os usuários ativos ou apenas os
 * que possuem um determinado papel global.
 */
enum PublicoComunicado: string
{
    public function label(): string
    {
        return match ($this) {
            self::Todos => 'Todos os usuários ativos',
            self::Papel => 'Usuários de um papel',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
    case Todos = 'todos';
    case Papel = 'papel';
}
