<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Categoria visual de uma notificação in-app. Define rótulo (PT-BR), a variante
 * de cor (casada com badges/toasts) e o ícone Tabler exibido no sino/central.
 */
enum TipoNotificacao: string
{
    public function label(): string
    {
        return match ($this) {
            self::Info => 'Informação',
            self::Sucesso => 'Sucesso',
            self::Aviso => 'Aviso',
            self::Erro => 'Erro',
        };
    }

    /**
     * Variante de cor usada por badges/ícones (primary, success, warning, danger).
     */
    public function variant(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Sucesso => 'success',
            self::Aviso => 'warning',
            self::Erro => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Info => 'tabler--info-circle',
            self::Sucesso => 'tabler--circle-check',
            self::Aviso => 'tabler--alert-triangle',
            self::Erro => 'tabler--alert-octagon',
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
    case Info = 'info';
    case Sucesso = 'sucesso';
    case Aviso = 'aviso';
    case Erro = 'erro';
}
