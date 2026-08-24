<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums;

enum StatusImport: string
{
    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::PROCESSANDO => 'Processando',
            self::CONCLUIDO => 'Concluído',
            self::FALHOU => 'Falhou',
        };
    }
    case PENDENTE = 'pendente';
    case PROCESSANDO = 'processando';
    case CONCLUIDO = 'concluido';
    case FALHOU = 'falhou';
}
