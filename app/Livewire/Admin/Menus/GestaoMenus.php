<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Menus;

use App\Actions\Admin\AlternarPermissaoPerfilAction;
use App\Actions\Admin\Menu\CriarGrupoMenuAction;
use App\Actions\Admin\Menu\CriarSecaoMenuAction;
use App\Actions\Admin\Menu\ExcluirPersonalizacaoCustomAction;
use App\Actions\Admin\Menu\ReordenarItensMenuAction;
use App\Actions\Admin\Menu\ReordenarSecoesMenuAction;
use App\Actions\Admin\Menu\RestaurarMenuAction;
use App\Actions\Admin\Menu\SalvarPersonalizacaoMenuAction;
use App\DTOs\Admin\MenuPersonalizacaoDTO;
use App\Enums\TipoPersonalizacaoMenu;
use App\Exceptions\AccessException;
use App\Models\AdminUser;
use App\Models\MenuPersonalizacao;
use App\Policies\RolePolicy;
use App\Services\Admin\Menu\MenuService;
use App\Support\Menu\IconesMenu;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Tela de Gestão de Menus: reordena (drag & drop em 3 níveis), cria seções e
 * grupos (submenus), renomeia, troca ícones e ativa/desativa os registros.
 * Itens vêm do config (registry); seções/grupos custom e todo o arranjo são
 * apresentação no banco. A visibilidade por perfil é o próprio ACL.
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Gestão de menus')]
class GestaoMenus extends Component
{
    public ?string $editandoTipo = null;

    public ?string $editandoKey = null;

    public bool $editandoEhCustom = false;

    public string $label = '';

    public string $icone = '';

    public bool $ativo = true;

    public string $novaSecaoLabel = '';

    public string $novoGrupoLabel = '';

    public string $novoGrupoIcone = 'tabler--folder';

    public function mount(): void
    {
        $user = auth('admin')->user();

        if ($user === null || ! $user->can('configuracoes.menus')) {
            throw new AuthorizationException('Acesso negado.');
        }
    }

    /**
     * Drag & drop de itens/grupos — containers `secao:<key>` | `grupo:<key>`.
     *
     * @param  array<string, list<string>>  $ordens
     */
    public function reordenarItens(
        string $itemKey,
        string $containerKey,
        array $ordens,
        ReordenarItensMenuAction $action,
    ): void {
        try {
            $action->execute($itemKey, $containerKey, $ordens);
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

    public function criarSecao(CriarSecaoMenuAction $action): void
    {
        $this->validate(
            ['novaSecaoLabel' => ['required', 'string', 'max:60']],
            attributes: ['novaSecaoLabel' => 'nome da seção'],
        );

        $action->execute($this->novaSecaoLabel);

        $this->reset('novaSecaoLabel');
        unset($this->estrutura);
        $this->dispatch('menus-fechar-modais');
        $this->dispatch('toast', variant: 'success', message: 'Seção criada.');
    }

    public function criarGrupo(string $secaoKey, CriarGrupoMenuAction $action): void
    {
        $this->validate(
            [
                'novoGrupoLabel' => ['required', 'string', 'max:60'],
                'novoGrupoIcone' => ['required', Rule::in(IconesMenu::disponiveis())],
            ],
            attributes: ['novoGrupoLabel' => 'nome do grupo', 'novoGrupoIcone' => 'ícone'],
        );

        try {
            $action->execute($this->novoGrupoLabel, $this->novoGrupoIcone, $secaoKey);
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', variant: 'danger', message: $e->getMessage());

            return;
        }

        $this->reset('novoGrupoLabel');
        $this->novoGrupoIcone = 'tabler--folder';
        unset($this->estrutura);
        $this->dispatch('menus-fechar-modais');
        $this->dispatch('toast', variant: 'success', message: 'Grupo criado. Arraste itens para dentro dele.');
    }

    /**
     * Abre o drawer de edição com os valores efetivos do registro.
     */
    public function editar(string $tipo, string $key): void
    {
        $detalhe = $this->detalheDe($tipo, $key);

        if ($detalhe === null) {
            $this->dispatch('toast', variant: 'danger', message: 'Registro de menu não encontrado.');

            return;
        }

        $this->resetValidation();
        $this->editandoTipo = $tipo;
        $this->editandoKey = $key;
        $this->editandoEhCustom = $detalhe['eCustom'];
        $this->label = $detalhe['label'];
        $this->icone = $detalhe['icone'] ?? '';
        $this->ativo = $detalhe['ativo'] ?? true;

        $this->dispatch('menus-abrir-editor');
    }

    public function salvarEdicao(SalvarPersonalizacaoMenuAction $action): void
    {
        if ($this->editandoTipo === null || $this->editandoKey === null) {
            return;
        }

        $this->validate();

        try {
            $action->execute(MenuPersonalizacaoDTO::fromArray([
                'tipo' => $this->editandoTipo,
                'key' => $this->editandoKey,
                'label' => $this->label,
                'icone' => $this->editandoTipo === TipoPersonalizacaoMenu::Secao->value ? null : $this->icone,
                'ativo' => $this->ativo,
            ]));
        } catch (InvalidArgumentException) {
            $this->dispatch('toast', variant: 'danger', message: 'Não foi possível salvar a personalização.');

            return;
        }

        unset($this->estrutura);
        $this->dispatch('menus-fechar-editor');
        $this->dispatch('toast', variant: 'success', message: 'Menu atualizado.');
    }

    /**
     * Liga/desliga um item globalmente (some do menu para todos; o acesso às
     * páginas continua regido pelo ACL).
     */
    public function alternarAtivo(string $itemKey, SalvarPersonalizacaoMenuAction $action): void
    {
        $item = $this->detalheDe(TipoPersonalizacaoMenu::Item->value, $itemKey);

        if ($item === null) {
            return;
        }

        $action->execute(MenuPersonalizacaoDTO::fromArray([
            'tipo' => TipoPersonalizacaoMenu::Item->value,
            'key' => $itemKey,
            'label' => $item['label'],
            'icone' => $item['icone'],
            'ativo' => ! $item['ativo'],
        ]));

        // Mantém o drawer coerente se o item editado foi alternado por fora.
        if ($this->editandoKey === $itemKey) {
            $this->ativo = ! $item['ativo'];
        }

        unset($this->estrutura);
        $this->dispatch('toast', variant: 'success', message: $item['ativo']
            ? 'Item desativado: não aparece mais no menu.'
            : 'Item reativado no menu.');
    }

    /**
     * Liga/desliga um grupo (inativo esconde o grupo e os filhos da sidebar).
     */
    public function alternarAtivoGrupo(string $grupoKey): void
    {
        $grupo = MenuPersonalizacao::query()
            ->where('tipo', TipoPersonalizacaoMenu::Grupo)
            ->where('key', $grupoKey)
            ->first();

        if ($grupo === null) {
            return;
        }

        $grupo->ativo = ! $grupo->ativo;
        $grupo->save();

        app(MenuService::class)->invalidarCache();

        if ($this->editandoKey === $grupoKey) {
            $this->ativo = $grupo->ativo;
        }

        unset($this->estrutura);
        $this->dispatch('toast', variant: 'success', message: $grupo->ativo
            ? 'Grupo reativado no menu.'
            : 'Grupo desativado: ele e seus itens não aparecem mais no menu.');
    }

    /**
     * Concede/revoga a permissão do item editado em um perfil (toggle de
     * visibilidade — controla o módulo inteiro, menu e páginas).
     */
    public function alternarPerfil(int $roleId, AlternarPermissaoPerfilAction $action): void
    {
        $permissao = $this->permissaoDoItemEditado();

        if ($permissao === null) {
            return;
        }

        $role = Role::query()->where('guard_name', 'admin')->find($roleId);

        if ($role === null) {
            return;
        }

        // Mesmo gate do hub de Controle de Acesso (Policy direta — o bypass
        // do super-admin no Gate não mascara role protegida/hierarquia).
        $user = auth('admin')->user();

        if (! $user instanceof AdminUser || ! app(RolePolicy::class)->update($user, $role)) {
            $this->dispatch('toast', variant: 'danger', message: 'Você não tem permissão para gerir este perfil.');

            return;
        }

        $conceder = ! $role->hasPermissionTo($permissao);

        try {
            $action->execute($roleId, $permissao, $conceder);
        } catch (AccessException $e) {
            $this->dispatch('toast', variant: 'danger', message: $e->getMessage());

            return;
        }

        unset($this->perfis);
        $this->dispatch('toast', variant: 'success', message: $conceder
            ? "Permissão concedida ao perfil {$role->name}."
            : "Permissão revogada do perfil {$role->name}.");
    }

    public function solicitarExcluirCustom(string $tipo, string $key): void
    {
        $rotulo = $tipo === TipoPersonalizacaoMenu::Grupo->value ? 'grupo' : 'seção';

        $this->dispatch(
            'confirm',
            title: "Excluir {$rotulo}?",
            text: $tipo === TipoPersonalizacaoMenu::Grupo->value
                ? 'Os itens do grupo voltam para a posição original deles no menu.'
                : 'Os itens desta seção voltam para a seção original deles no menu.',
            destructive: true,
            onConfirm: 'menus::excluir-custom',
            params: ['tipo' => $tipo, 'key' => $key],
        );
    }

    #[On('menus::excluir-custom')]
    public function excluirCustom(string $tipo, string $key, ExcluirPersonalizacaoCustomAction $action): void
    {
        $enumTipo = TipoPersonalizacaoMenu::tryFrom($tipo);

        if ($enumTipo === null) {
            return;
        }

        try {
            $action->execute($enumTipo, $key);
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', variant: 'danger', message: $e->getMessage());

            return;
        }

        unset($this->estrutura);
        $this->dispatch('menus-fechar-editor');
        $this->dispatch('toast', variant: 'success', message: 'Registro excluído do menu.');
    }

    public function solicitarRestaurar(string $tipo, string $key): void
    {
        $this->dispatch(
            'confirm',
            title: 'Restaurar ao padrão?',
            text: 'As personalizações deste registro do menu serão removidas.',
            destructive: true,
            onConfirm: 'menus::restaurar-registro',
            params: ['tipo' => $tipo, 'key' => $key],
        );
    }

    #[On('menus::restaurar-registro')]
    public function restaurarRegistro(string $tipo, string $key, RestaurarMenuAction $action): void
    {
        $enumTipo = TipoPersonalizacaoMenu::tryFrom($tipo);

        if ($enumTipo === null) {
            return;
        }

        $action->execute($enumTipo, $key);

        unset($this->estrutura);
        $this->dispatch('menus-fechar-editor');
        $this->dispatch('toast', variant: 'success', message: 'Registro restaurado ao padrão.');
    }

    public function solicitarRestaurarTudo(): void
    {
        $this->dispatch(
            'confirm',
            title: 'Restaurar menu padrão?',
            text: 'Todas as personalizações serão removidas — inclusive os grupos e seções criados por aqui, que serão excluídos.',
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

    public function solicitarLimparOrfas(): void
    {
        $this->dispatch(
            'confirm',
            title: 'Limpar personalizações órfãs?',
            text: 'As personalizações que apontam para itens removidos do registro do menu serão excluídas.',
            destructive: true,
            onConfirm: 'menus::limpar-orfas',
            params: [],
        );
    }

    #[On('menus::limpar-orfas')]
    public function limparOrfas(RestaurarMenuAction $action): void
    {
        $removidas = $action->removerOrfas();

        unset($this->estrutura);
        $this->dispatch('toast', variant: 'success', message: $removidas > 0
            ? 'Personalizações órfãs removidas.'
            : 'Nenhuma personalização órfã encontrada.');
    }

    /**
     * @return array{secoes: list<array<string, mixed>>, orfas: \Illuminate\Support\Collection<int, MenuPersonalizacao>}
     */
    #[Computed]
    public function estrutura(): array
    {
        return app(MenuService::class)->estruturaParaGestao();
    }

    /**
     * Perfis do guard admin (com permissões carregadas) para os toggles de
     * visibilidade do drawer.
     *
     * @return Collection<int, Role>
     */
    #[Computed]
    public function perfis(): Collection
    {
        return Role::query()
            ->where('guard_name', 'admin')
            ->orderByDesc('nivel')
            ->orderBy('name')
            ->with('permissions')
            ->get();
    }

    /**
     * Permissão vinculada ao item aberto no drawer (resolvida no servidor —
     * o payload do cliente nunca escolhe a permissão).
     */
    public function permissaoDoItemEditado(): ?string
    {
        if ($this->editandoTipo !== TipoPersonalizacaoMenu::Item->value || $this->editandoKey === null) {
            return null;
        }

        return app(MenuService::class)->permissaoDoItem($this->editandoKey);
    }

    public function render(): View
    {
        return view('livewire.admin.menus.gestao-menus');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        // Customs (grupos/seções criados pela tela) não têm padrão p/ herdar:
        // o nome é obrigatório; grupo também exige ícone da lista curada.
        $labelObrigatorio = $this->editandoEhCustom || $this->editandoTipo === TipoPersonalizacaoMenu::Grupo->value;

        return [
            'label' => [$labelObrigatorio ? 'required' : 'nullable', 'string', 'max:80'],
            'icone' => [
                $this->editandoTipo === TipoPersonalizacaoMenu::Grupo->value ? 'required' : 'nullable',
                'string',
                Rule::in(IconesMenu::disponiveis()),
            ],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'label.required' => 'Informe o nome.',
            'icone.required' => 'Escolha um ícone da lista de sugestões.',
            'icone.in' => 'Escolha um ícone da lista de sugestões.',
        ];
    }

    /**
     * Valores efetivos (config + personalização) de um registro da estrutura.
     *
     * @return array<string, mixed>|null
     */
    private function detalheDe(string $tipo, string $key): ?array
    {
        foreach ($this->estrutura()['secoes'] as $secao) {
            if ($tipo === TipoPersonalizacaoMenu::Secao->value && $secao['key'] === $key) {
                return [
                    'label' => $secao['title'],
                    'icone' => null,
                    'ativo' => true,
                    'eCustom' => $secao['eCustom'],
                ];
            }

            foreach ($secao['items'] as $entry) {
                if ($entry['tipo'] === 'grupo') {
                    if ($tipo === TipoPersonalizacaoMenu::Grupo->value && $entry['key'] === $key) {
                        return [
                            'label' => $entry['label'],
                            'icone' => $entry['icon'],
                            'ativo' => $entry['ativo'],
                            'eCustom' => true,
                        ];
                    }

                    if ($tipo === TipoPersonalizacaoMenu::Item->value) {
                        foreach ($entry['children'] as $filho) {
                            if ($filho['key'] === $key) {
                                return [
                                    'label' => $filho['label'],
                                    'icone' => $filho['icon'],
                                    'ativo' => $filho['ativo'],
                                    'eCustom' => false,
                                ];
                            }
                        }
                    }

                    continue;
                }

                if ($tipo === TipoPersonalizacaoMenu::Item->value && $entry['key'] === $key) {
                    return [
                        'label' => $entry['label'],
                        'icone' => $entry['icon'],
                        'ativo' => $entry['ativo'],
                        'eCustom' => false,
                    ];
                }
            }
        }

        return null;
    }
}
