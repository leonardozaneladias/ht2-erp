<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

final readonly class SyncPermissoesPerfilDTO
{
    /**
     * @param  list<string>  $permissoes
     */
    public function __construct(
        public int $roleId,
        public array $permissoes,
    ) {}

    /**
     * @param  array{roleId: int, permissoes?: list<string>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            roleId: $data['roleId'],
            permissoes: $data['permissoes'] ?? [],
        );
    }
}
