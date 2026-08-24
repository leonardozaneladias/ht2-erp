<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Money;

use InvalidArgumentException;
use NumberFormatter;

/**
 * Value Object imutável para valores monetários em centavos.
 *
 * Regras:
 * - Armazena o valor em centavos (int).
 * - Nunca negativo (lança InvalidArgumentException).
 * - Operações aritméticas retornam nova instância.
 * - Formatação localizada via Intl (pt-BR por padrão).
 */
final readonly class Money
{
    private function __construct(private int $centavos)
    {
        if ($centavos < 0) {
            throw new InvalidArgumentException("Valor monetário não pode ser negativo: {$centavos}");
        }
    }

    public static function fromCentavos(int $centavos): self
    {
        return new self($centavos);
    }

    public static function fromReais(float|string $reais): self
    {
        return new self((int) round((float) $reais * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    // ---- Accessors -----------------------------------------------------------

    public function centavos(): int
    {
        return $this->centavos;
    }

    public function toInt(): int
    {
        return $this->centavos;
    }

    // ---- Aritmética (retorna nova instância) ----------------------------------

    public function mais(self $outro): self
    {
        return new self($this->centavos + $outro->centavos);
    }

    public function menos(self $outro): self
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

    public function eZero(): bool
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
    public function formatado(): string
    {
        $formatter = new NumberFormatter('pt_BR', NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($this->reais(), 'BRL');
    }

    public function __toString(): string
    {
        return $this->formatado();
    }
}
