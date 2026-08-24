<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin;

final readonly class SyncRolesDTO
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public int $adminUserId,
        public array $roles,
    ) {}

    /**
     * @param  array{adminUserId: int, roles?: list<string>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            adminUserId: $data['adminUserId'],
            roles: $data['roles'] ?? [],
        );
    }
}
