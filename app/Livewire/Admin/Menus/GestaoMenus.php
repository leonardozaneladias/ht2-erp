<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Menus;

use App\Actions\Admin\Menu\ReordenarItensMenuAction;
use App\Actions\Admin\Menu\ReordenarSecoesMenuAction;
use App\Actions\Admin\Menu\RestaurarMenuAction;
use App\Services\Admin\Menu\MenuService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tela de Gestão de Menus: reordena (drag & drop), renomeia, troca ícones e
 * ativa/desativa os itens registrados em config/admin-menu.php. A visibilidade
 * por perfil é o próprio ACL (permissão de cada item × perfis).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Gestão de menus')]
class GestaoMenus extends Component
{
    public function mount(): void
    {
        $user = auth('admin')->user();

        if ($user === null || ! $user->can('configuracoes.menus')) {
            throw new AuthorizationException('Acesso negado.');
        }
    }

    /**
     * Drag & drop de itens — dentro da seção ou entre seções (group).
     *
     * @param  array<string, list<string>>  $ordens
     */
    public function reordenarItens(
        string $itemKey,
        string $secaoKey,
        array $ordens,
        ReordenarItensMenuAction $action,
    ): void {
        try {
            $action->execute($itemKey, $secaoKey, $ordens);
        } catch (InvalidArgumentException) {
            $this->dispatch('toast', variant: 'danger', message: 'Não foi possível reordenar o menu.');

            return;
        }

        unset($this->estrutura);
        $this->dispatch('toast', variant: 'success', message: 'Ordem do menu atualizada.');
    }

    /**
     * Drag & drop das seções.
     *
     * @param  list<string>  $secaoKeys
     */
    public function reordenarSecoes(array $secaoKeys, ReordenarSecoesMenuAction $action): void
    {
        try {
            $action->execute($secaoKeys);
        } catch (InvalidArgumentException) {
            $this->dispatch('toast', variant: 'danger', message: 'Não foi possível reordenar as seções.');

            return;
        }

        unset($this->estrutura);
        $this->dispatch('toast', variant: 'success', message: 'Ordem das seções atualizada.');
    }

    public function solicitarRestaurarTudo(): void
    {
        $this->dispatch(
            'confirm',
            title: 'Restaurar menu padrão?',
            text: 'Todas as personalizações (ordem, nomes, ícones e itens desativados) serão removidas.',
            destructive: true,
            onConfirm: 'menus::restaurar-tudo',
            params: [],
        );
    }

    #[On('menus::restaurar-tudo')]
    public function restaurarTudo(RestaurarMenuAction $action): void
    {
        $removidas = $action->execute();

        unset($this->estrutura);
        $this->dispatch('toast', variant: 'success', message: $removidas > 0
            ? 'Menu restaurado para o padrão.'
            : 'O menu já está no padrão.');
    }

    /**
     * @return array{secoes: list<array<string, mixed>>, orfas: \Illuminate\Support\Collection<int, \App\Models\MenuPersonalizacao>}
     */
    #[Computed]
    public function estrutura(): array
    {
        return app(MenuService::class)->estruturaParaGestao();
    }

    public function render(): View
    {
        return view('livewire.admin.menus.gestao-menus');
    }
}
