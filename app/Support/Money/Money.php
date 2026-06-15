<?php

declare(strict_types=1);

namespace App\Support\Money;

use InvalidArgumentException;
use NumberFormatter;
use Stringable;

/**
 * Value Object imutável para valores monetários em centavos.
 *
 * Regras:
 * - Armazena o valor em centavos (int).
 * - Nunca negativo (lança InvalidArgumentException).
 * - Operações aritméticas retornam nova instância.
 * - Formatação localizada via Intl (pt-BR por padrão).
 */
final class Money implements Stringable
{
    public function __construct(
        public readonly int $centavos,
    ) {
        if ($centavos < 0) {
            throw new InvalidArgumentException("Valor monetário não pode ser negativo: {$centavos}");
        }
    }

    public static function deCentavos(int $centavos): self
    {
        return new self($centavos);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    // ---- Aritmética (retorna nova instância) ----------------------------------

    public function somar(self $outro): self
    {
        return new self($this->centavos + $outro->centavos);
    }

    public function subtrair(self $outro): self
    {
        return new self($this->centavos - $outro->centavos);
    }

    public function multiplicar(int|float $fator): self
    {
        return new self((int) round($this->centavos * $fator));
    }

    // ---- Comparação ----------------------------------------------------------

    public function igual(self $outro): bool
    {
        return $this->centavos === $outro->centavos;
    }

    public function maiorQue(self $outro): bool
    {
        return $this->centavos > $outro->centavos;
    }

    public function menorQue(self $outro): bool
    {
        return $this->centavos < $outro->centavos;
    }

    public function ehZero(): bool
    {
        return $this->centavos === 0;
    }

    // ---- Conversão -----------------------------------------------------------

    /** Valor em reais (float). Use apenas para exibição, nunca para cálculo. */
    public function reais(): float
    {
        return $this->centavos / 100;
    }

    /** Formata como BRL: R$ 1.234,56 */
    public function formatarBRL(): string
    {
        $formatter = new NumberFormatter('pt_BR', NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($this->reais(), 'BRL');
    }

    public function __toString(): string
    {
        return $this->formatarBRL();
    }
}
