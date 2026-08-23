<?php

declare(strict_types=1);

namespace HT2ML\Core\Policies;

use App\Services\Admin\HierarchyResolver;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\PermissionGrant;

class PermissionGrantPolicy
{
    public function __construct(private readonly HierarchyResolver $hierarchy) {}

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('acessos.gerenciar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('acessos.conceder');
    }

    public function deny(AdminUser $auth): bool
    {
        return $auth->can('acessos.revogar');
    }

    public function delete(AdminUser $auth, PermissionGrant $grant): bool
    {
        if (! $auth->can('acessos.revogar')) {
            return false;
        }

        $alvo = AdminUser::find($grant->admin_user_id);

        return $alvo === null || $this->hierarchy->podeGerir($auth, $alvo);
    }
}
