<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

use App\Enums\ModuloAcesso;

final readonly class PermissionDefinitionDTO
{
    public function __construct(
        public string $nome,
        public ModuloAcesso $modulo,
        public string $label,
        public ?string $descricao = null,
    ) {}

    /**
     * @param  array{nome: string, modulo: string|ModuloAcesso, label: string, descricao?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        $modulo = $data['modulo'] instanceof ModuloAcesso
            ? $data['modulo']
            : ModuloAcesso::from($data['modulo']);

        return new self(
            nome: $data['nome'],
            modulo: $modulo,
            label: $data['label'],
            descricao: $data['descricao'] ?? null,
        );
    }
}
