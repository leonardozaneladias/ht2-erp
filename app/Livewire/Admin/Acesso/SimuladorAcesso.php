<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Acesso;

use App\Models\AdminUser;
use App\Services\Admin\AcessoService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true])]
#[Title('Simulador de acesso')]
class SimuladorAcesso extends Component
{
    #[Url(as: 'u', except: '')]
    public string $usuarioId = '';

    public function mount(): void
    {
        $user = Auth::guard('admin')->user();

        if ($user === null || ! $user->can('acessos.simular')) {
            throw new AuthorizationException('Acesso negado.');
        }
    }

    public function render(AcessoService $service): View
    {
        $usuario = $this->usuarioId !== ''
            ? AdminUser::with('roles')->find((int) $this->usuarioId)
            : null;

        $acessoPorModulo = $usuario !== null ? $service->simular($usuario) : collect();

        return view('livewire.admin.acesso.simulador-acesso', [
            'usuario' => $usuario,
            'usuarios' => $this->usuariosParaSelect(),
            'acessoPorModulo' => $acessoPorModulo,
            'resumo' => $this->resumo($acessoPorModulo),
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function usuariosParaSelect(): array
    {
        return AdminUser::query()
            ->orderBy('nome')
            ->get(['id', 'nome', 'email'])
            ->map(static fn (AdminUser $u): array => [
                'value' => (string) $u->id,
                'label' => "{$u->nome} ({$u->email})",
            ])
            ->all();
    }

    /**
     * @param  Collection<string, Collection<int, \App\DTOs\Admin\AcessoEfetivoDTO>>  $acessoPorModulo
     * @return array{permitidos: int, negados: int, total: int}
     */
    protected function resumo(Collection $acessoPorModulo): array
    {
        $todos = $acessoPorModulo->flatten(1);

        return [
            'permitidos' => $todos->filter(static fn ($dto): bool => $dto->permitido)->count(),
            'negados' => $todos->filter(static fn ($dto): bool => ! $dto->permitido)->count(),
            'total' => $todos->count(),
        ];
    }
}
