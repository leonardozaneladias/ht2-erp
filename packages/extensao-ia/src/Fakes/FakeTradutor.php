<?php

declare(strict_types=1);

namespace HT2ML\Ia\Fakes;

use HT2ML\Ia\Contracts\TradutorInterface;

/**
 * Tradutor de desenvolvimento: marca o texto como traduzido sem chamar
 * provedor nenhum, para o produto funcionar de ponta a ponta sem credencial.
 */
final class FakeTradutor implements TradutorInterface
{
    public function traduzir(string $texto, string $idiomaOrigem, string $idiomaDestino): array
    {
        return ['texto' => "[{$idiomaOrigem}→{$idiomaDestino}] {$texto}"];
    }

    public function identificador(): string
    {
        return 'fake';
    }
}
