<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

use App\Enums\TipoPersonalizacaoMenu;

final readonly class MenuPersonalizacaoDTO
{
    public function __construct(
        public TipoPersonalizacaoMenu $tipo,
        public string $key,
        public ?string $label,
        public ?string $icone,
        public bool $ativo,
    ) {}

    /**
     * @param  array<string, mixed>  $dados
     */
    public static function fromArray(array $dados): self
    {
        $label = trim((string) ($dados['label'] ?? ''));
        $icone = trim((string) ($dados['icone'] ?? ''));

        return new self(
            tipo: TipoPersonalizacaoMenu::from((string) $dados['tipo']),
            key: (string) $dados['key'],
            label: $label === '' ? null : $label,
            icone: $icone === '' ? null : $icone,
            ativo: (bool) ($dados['ativo'] ?? true),
        );
    }
}
