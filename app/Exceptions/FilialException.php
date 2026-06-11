<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class FilialException extends RuntimeException
{
    public static function matrizProtegida(): self
    {
        return new self('A Matriz não pode ser desativada ou excluída.');
    }
}
