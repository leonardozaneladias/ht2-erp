<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums\Referencia;

/**
 * Origem de uma linha de catálogo de referência.
 *
 * Separa duas populações que não se cruzam: o que veio da fonte autoritativa
 * (IBGE, Bacen, ISO) via `referencia:sync`, e o que o cliente criou.
 *
 * A linha sincronizada é somente-leitura — e é isso que elimina o problema de
 * reconciliação: o sync nunca tem edição manual para sobrescrever, porque
 * editar linha sincronizada não é possível. A linha do cliente é totalmente
 * editável e o sync nunca a toca.
 */
enum OrigemRegistro: string
{
    public function label(): string
    {
        return match ($this) {
            self::Sincronizado => 'Sincronizado',
            self::Manual => 'Cadastrado aqui',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::Sincronizado => 'Mantido pelo referencia:sync a partir da fonte oficial. Somente leitura.',
            self::Manual => 'Criado nesta instalação. Editável, e o sync não o altera.',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Sincronizado => 'neutral',
            self::Manual => 'primary',
        };
    }

    case Sincronizado = 'sync';
    case Manual = 'manual';
}
