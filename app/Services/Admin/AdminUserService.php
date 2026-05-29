<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminUserService
{
    /**
     * Lista paginada com filtros simples; não recebe Request, não retorna view.
     *
     * @param  array{busca?: ?string, status?: ?string, role?: ?string, sort?: ?string, dir?: ?string}  $filtros
     * @return LengthAwarePaginator<int, AdminUser>
     */
    public function listarPaginado(array $filtros = [], int $porPagina = 15): LengthAwarePaginator
    {
        return $this->aplicarFiltros(AdminUser::query()->with('roles'), $filtros)
            ->paginate($porPagina)
            ->withQueryString();
    }

    /**
     * @param  array{busca?: ?string, status?: ?string, role?: ?string, sort?: ?string, dir?: ?string}  $filtros
     * @param  Builder<AdminUser>  $query
     * @return Builder<AdminUser>
     */
    private function aplicarFiltros(Builder $query, array $filtros): Builder
    {
        if (! empty($filtros['busca'])) {
            $termo = '%' . mb_strtolower((string) $filtros['busca']) . '%';
            $query->where(function (Builder $sub) use ($termo): void {
                $sub->whereRaw('LOWER(nome) LIKE ?', [$termo])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$termo]);
            });
        }

        $status = $filtros['status'] ?? null;
        if ($status === 'ativo' || $status === 'inativo') {
            $query->where('ativo', $status === 'ativo');
        }

        if (! empty($filtros['role'])) {
            $query->whereHas('roles', fn (Builder $sub) => $sub->where('name', $filtros['role']));
        }

        $colunaOrdenacao = in_array($filtros['sort'] ?? '', ['nome', 'email', 'created_at', 'last_login_at'], true)
            ? $filtros['sort']
            : 'nome';
        $direcao = ($filtros['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($colunaOrdenacao, $direcao);
    }
}
