<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Models\Filial;
use Illuminate\Support\Collection;

/**
 * Resolve as empresas que um usuário pode acessar e a empresa padrão do contexto.
 * Super-admin (papel global) enxerga todas as empresas ativas; os demais, apenas
 * as concedidas via admin_user_empresa.
 */
final class TenantResolver
{
    /**
     * @return Collection<int, Empresa>
     */
    public function empresasDisponiveis(AdminUser $user): Collection
    {
        if ($this->ehSuperAdmin($user)) {
            return Empresa::query()->ativas()->orderBy('nome')->get();
        }

        return $user->empresasAcessiveis()
            ->where('empresas.ativo', true)
            ->orderBy('empresas.nome')
            ->get();
    }

    public function podeAcessar(AdminUser $user, int $empresaId): bool
    {
        if ($this->ehSuperAdmin($user)) {
            return Empresa::query()->whereKey($empresaId)->where('ativo', true)->exists();
        }

        return $user->temAcessoAEmpresa($empresaId);
    }

    /**
     * Empresa que deve ficar ativa por padrão: a última escolhida (se ainda
     * acessível) ou a primeira disponível. Null quando o usuário não tem empresa.
     */
    public function empresaPadrao(AdminUser $user): ?int
    {
        $atual = $user->empresa_ativa_id;

        if (is_int($atual) && $this->podeAcessar($user, $atual)) {
            return $atual;
        }

        return $this->empresasDisponiveis($user)->first()?->getKey();
    }

    /**
     * Filiais ativas da empresa que o usuário pode selecionar. Quem tem
     * "todas as filiais" (ou super-admin) vê todas; os demais, só as concedidas.
     *
     * @return Collection<int, Filial>
     */
    public function filiaisDisponiveis(AdminUser $user, int $empresaId): Collection
    {
        $base = Filial::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome');

        if ($this->ehSuperAdmin($user) || $this->temTodasFiliais($user, $empresaId)) {
            return $base->get();
        }

        $ids = $user->filiaisAcessiveis()
            ->where('filiais.empresa_id', $empresaId)
            ->pluck('filiais.id')
            ->all();

        return $ids === [] ? collect() : $base->whereIn('id', $ids)->get();
    }

    private function temTodasFiliais(AdminUser $user, int $empresaId): bool
    {
        return $user->empresasAcessiveis()
            ->where('empresas.id', $empresaId)
            ->wherePivot('todas_filiais', true)
            ->exists();
    }

    private function ehSuperAdmin(AdminUser $user): bool
    {
        return $user->hasRole((string) config('access.super_admin_role', 'super-admin'), 'admin');
    }
}
