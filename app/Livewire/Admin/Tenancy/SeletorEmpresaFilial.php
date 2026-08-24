<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tenancy;

use HT2ML\Core\Actions\Admin\DefinirEmpresaAtivaAction;
use HT2ML\Core\Actions\Admin\DefinirFilialAtivaAction;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Tenancy\TenantContext;
use HT2ML\Core\Support\Tenancy\TenantResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Seletor de empresa/filial ativas na topbar. Lista apenas o que o usuário
 * acessa; a troca delega às Actions de contexto e recarrega a página para
 * refletir o novo escopo (dados e branding).
 */
class SeletorEmpresaFilial extends Component
{
    public function definirEmpresa(?int $empresaId, DefinirEmpresaAtivaAction $action): void
    {
        $user = Auth::guard('admin')->user();

        if (! $user instanceof AdminUser) {
            return;
        }

        $action->execute($user, $empresaId);
        $this->recarregar();
    }

    public function definirFilial(?int $filialId, DefinirFilialAtivaAction $action): void
    {
        $user = Auth::guard('admin')->user();

        if (! $user instanceof AdminUser) {
            return;
        }

        $action->execute($user, $filialId);
        $this->recarregar();
    }

    public function render(): View
    {
        $user = Auth::guard('admin')->user();

        if (! $user instanceof AdminUser) {
            return view('livewire.admin.tenancy.seletor-empresa-filial', $this->vazio());
        }

        $resolver = app(TenantResolver::class);
        $context = app(TenantContext::class);

        $empresas = $resolver->empresasDisponiveis($user);
        $empresaAtivaId = $context->empresaAtivaId();
        $empresaAtiva = $empresaAtivaId !== null ? $empresas->firstWhere('id', $empresaAtivaId) : null;

        $filiais = $empresaAtivaId !== null
            ? $resolver->filiaisDisponiveis($user, $empresaAtivaId)
            : collect();
        $filialAtivaId = $context->filialAtivaId();
        $filialAtiva = $filialAtivaId !== null ? $filiais->firstWhere('id', $filialAtivaId) : null;

        return view('livewire.admin.tenancy.seletor-empresa-filial', [
            'empresas' => $empresas,
            'filiais' => $filiais,
            'empresaAtivaId' => $empresaAtivaId,
            'empresaAtivaNome' => $empresaAtiva?->nome,
            'filialAtivaId' => $filialAtivaId,
            'filialAtivaNome' => $filialAtiva?->nome,
        ]);
    }

    private function recarregar(): void
    {
        $this->redirect(request()->header('Referer') ?? route('admin.dashboard'), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function vazio(): array
    {
        return [
            'empresas' => collect(),
            'filiais' => collect(),
            'empresaAtivaId' => null,
            'empresaAtivaNome' => null,
            'filialAtivaId' => null,
            'filialAtivaNome' => null,
        ];
    }
}
