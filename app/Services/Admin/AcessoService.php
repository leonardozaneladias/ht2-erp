<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\DTOs\Admin\AcessoEfetivoDTO;
use App\Models\AdminUser;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

/**
 * Consultas read-only para as telas de governança de acesso (matriz, simulador).
 */
final class AcessoService
{
    public function __construct(private readonly AccessResolver $resolver) {}

    /**
     * Permissões do catálogo agrupadas por módulo (para a Matriz).
     *
     * @return Collection<string, Collection<int, Permission>>
     */
    public function permissoesPorModulo(): Collection
    {
        /** @var Collection<int, Permission> $permissoes */
        $permissoes = Permission::query()
            ->where('guard_name', 'admin')
            ->orderBy('modulo')
            ->orderBy('label')
            ->get()
            ->values();

        return $permissoes->groupBy(
            fn (Permission $permissao): string => (string) ($permissao->getAttribute('modulo') ?? 'outros'),
        );
    }

    /**
     * Acesso efetivo do usuário, explicado permissão a permissão, agrupado por módulo.
     *
     * @return Collection<string, Collection<int, AcessoEfetivoDTO>>
     */
    public function simular(AdminUser $user): Collection
    {
        return Permission::query()
            ->where('guard_name', 'admin')
            ->orderBy('modulo')
            ->orderBy('label')
            ->get()
            ->groupBy(fn (Permission $permissao): string => (string) ($permissao->getAttribute('modulo') ?? 'outros'))
            ->map(fn (Collection $permissoes): Collection => $permissoes->map(
                fn (Permission $permissao): AcessoEfetivoDTO => $this->resolver->explicar($user, $permissao->name),
            )->values());
    }
}
