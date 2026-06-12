<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoPersonalizacaoMenu: string
{
    public function label(): string
    {
        return match ($this) {
            self::Item => 'Item de menu',
            self::Secao => 'Seção de menu',
        };
    }

    case Item = 'item';
    case Secao = 'secao';
}
