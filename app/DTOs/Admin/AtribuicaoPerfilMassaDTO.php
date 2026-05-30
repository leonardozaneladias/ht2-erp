<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

final readonly class AtribuicaoPerfilMassaDTO
{
    /**
     * @param  list<int>  $adminUserIds
     * @param  list<string>  $roles
     */
    public function __construct(
        public array $adminUserIds,
        public array $roles,
        public bool $substituir = false,
    ) {}

    /**
     * @param  array{adminUserIds?: list<int>, roles?: list<string>, substituir?: bool}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            adminUserIds: $data['adminUserIds'] ?? [],
            roles: $data['roles'] ?? [],
            substituir: $data['substituir'] ?? false,
        );
    }
}
