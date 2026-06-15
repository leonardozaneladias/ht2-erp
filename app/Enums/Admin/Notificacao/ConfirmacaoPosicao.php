<?php

declare(strict_types=1);

namespace App\Enums\Admin\Notificacao;

/**
 * Posição do diálogo de confirmação (SweetAlert2). O valor casa com a opção
 * `position` do SweetAlert, consumida em resources/js/admin/confirm.js.
 */
enum ConfirmacaoPosicao: string
{
    public function label(): string
    {
        return match ($this) {
            self::CENTRO => 'Centralizado',
            self::TOPO => 'Topo',
        };
    }

    /** Valor `position` aceito pelo SweetAlert2. */
    public function swal(): string
    {
        return $this->value;
    }

    public static function padrao(): self
    {
        return self::CENTRO;
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $c): array => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }

    /** @return array<int, string> */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
    case CENTRO = 'center';
    case TOPO = 'top';
}
