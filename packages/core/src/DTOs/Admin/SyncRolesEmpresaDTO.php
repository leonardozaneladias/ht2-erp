<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin;

final readonly class SyncRolesEmpresaDTO
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public int $adminUserId,
        public int $empresaId,
        public array $roles,
    ) {}

    /**
     * @param  array{adminUserId: int, empresaId: int, roles?: list<string>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            adminUserId: $data['adminUserId'],
            empresaId: $data['empresaId'],
            roles: $data['roles'] ?? [],
        );
    }
}
