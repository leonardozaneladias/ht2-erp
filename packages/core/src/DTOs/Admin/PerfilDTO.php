<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin;

final readonly class PerfilDTO
{
    /**
     * @param  list<string>  $permissoes
     */
    public function __construct(
        public string $nome,
        public int $nivel,
        public ?string $descricao = null,
        public array $permissoes = [],
        public ?int $roleId = null,
    ) {}

    /**
     * @param  array{nome: string, nivel: int, descricao?: ?string, permissoes?: list<string>, roleId?: ?int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nome: $data['nome'],
            nivel: $data['nivel'],
            descricao: $data['descricao'] ?? null,
            permissoes: $data['permissoes'] ?? [],
            roleId: $data['roleId'] ?? null,
        );
    }
}
