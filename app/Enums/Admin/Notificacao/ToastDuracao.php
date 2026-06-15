<?php

declare(strict_types=1);

namespace App\Enums\Admin\Notificacao;

/**
 * Tempo que uma notificação fica na tela antes de sumir sozinha.
 * Erros recebem um acréscimo no motor JS para dar mais tempo de leitura.
 */
enum ToastDuracao: string
{
    public function label(): string
    {
        return match ($this) {
            self::CURTA => 'Curta (~3s)',
            self::MEDIA => 'Média (~4,5s)',
            self::LONGA => 'Longa (~7s)',
        };
    }

    /** Duração base em milissegundos (sucesso/info/aviso). */
    public function ms(): int
    {
        return match ($this) {
            self::CURTA => 3000,
            self::MEDIA => 4500,
            self::LONGA => 7000,
        };
    }

    public static function padrao(): self
    {
        return self::MEDIA;
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
    case CURTA = 'curta';
    case MEDIA = 'media';
    case LONGA = 'longa';
}
