<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Empresas;

use HT2ML\Core\Actions\Admin\AtualizarFilialAction;
use HT2ML\Core\Actions\Admin\CreateEmpresaAction;
use HT2ML\Core\Actions\Admin\CriarFilialAction;
use HT2ML\Core\Actions\Admin\UpdateEmpresaAction;
use HT2ML\Core\Contracts\Referencia\FonteDeMunicipios;
use HT2ML\Core\Contracts\Referencia\FonteDeUnidadesFederativas;
use HT2ML\Core\DTOs\Admin\EmpresaDTO;
use HT2ML\Core\DTOs\Admin\FilialDTO;
use HT2ML\Core\Exceptions\FilialException;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Models\Filial;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormEmpresa extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $empresaId = null;

    public string $nome = '';

    public string $razao_social = '';

    public string $cnpj = '';

    public string $inscricao_estadual = '';

    public string $telefone = '';

    public string $email = '';

    public string $site_url = '';

    public string $cor_primaria = '';

    public string $cor_secundaria = '';

    public string $cor_sucesso = '';

    public string $cor_warning = '';

    public string $cor_perigo = '';

    public string $cor_info = '';

    public bool $ativo = true;

    // Painel de filiais (apenas no modo edição).
    public bool $mostrarFormFilial = false;

    public ?int $filialEditandoId = null;

    public string $filial_nome = '';

    public string $filial_cnpj = '';

    public string $filial_inscricao_estadual = '';

    public string $filial_telefone = '';

    public string $filial_cidade = '';

    public string $filial_estado = '';

    public bool $filial_ativo = true;

    // Lixeira de filiais (D2): a gestão da lixeira de Filial vive aqui, no form
    // da empresa, reusando as permissões empresas.* (sem módulo ACL próprio).
    public bool $verFiliaisLixeira = false;

    public function mount(?int $empresa = null): void
    {
        if ($empresa !== null) {
            $alvo = Empresa::findOrFail($empresa);
            $this->authorize('update', $alvo);

            $this->empresaId = $alvo->id;
            $this->nome = $alvo->nome;
            $this->razao_social = (string) $alvo->razao_social;
            $this->cnpj = (string) $alvo->cnpj;
            $this->inscricao_estadual = (string) $alvo->inscricao_estadual;
            $this->telefone = (string) $alvo->telefone;
            $this->email = (string) $alvo->email;
            $this->site_url = (string) $alvo->site_url;
            $this->cor_primaria = (string) $alvo->cor_primaria;
            $this->cor_secundaria = (string) $alvo->cor_secundaria;
            $this->cor_sucesso = (string) $alvo->cor_sucesso;
            $this->cor_warning = (string) $alvo->cor_warning;
            $this->cor_perigo = (string) $alvo->cor_perigo;
            $this->cor_info = (string) $alvo->cor_info;
            $this->ativo = (bool) $alvo->ativo;

            return;
        }

        $this->authorize('create', Empresa::class);
    }

    public function salvar(CreateEmpresaAction $criar, UpdateEmpresaAction $atualizar): void
    {
        $dados = $this->validate();
        $dto = EmpresaDTO::fromArray($dados);

        if ($this->empresaId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'Empresa criada.');
        } else {
            $atualizar->execute(Empresa::findOrFail($this->empresaId), $dto);
            $this->notificarAposRedirect('success', 'Empresa atualizada.');
        }

        $this->redirect(route('admin.empresas.index'), navigate: true);
    }

    /**
     * Filiais da empresa (Matriz primeiro).
     *
     * @return Collection<int, Filial>
     */
    #[Computed]
    public function filiais(): Collection
    {
        if ($this->empresaId === null) {
            return collect();
        }

        $query = Filial::query()->where('empresa_id', $this->empresaId);

        if ($this->verFiliaisLixeira) {
            $query->onlyTrashed();
        }

        return $query
            ->orderByDesc('e_matriz')
            ->orderBy('nome')
            ->get();
    }

    /**
     * UFs para o select da filial (sigla => nome).
     *
     * @return array<string, string>
     */
    #[Computed]
    public function ufsDisponiveis(): array
    {
        return app(FonteDeUnidadesFederativas::class)->opcoes();
    }

    /**
     * Municípios da UF selecionada na filial (nome => nome) + a cidade atual, se
     * fora do catálogo. Carregado por UF para não despejar todos no DOM.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function municipiosDaFilial(): array
    {
        if ($this->filial_estado === '') {
            return [];
        }

        $nomes = app(FonteDeMunicipios::class)->daUnidadeFederativa($this->filial_estado);

        if ($this->filial_cidade !== '' && ! in_array($this->filial_cidade, $nomes, true)) {
            array_unshift($nomes, $this->filial_cidade);
        }

        return array_combine($nomes, $nomes);
    }

    /** Ao trocar a UF, limpa a cidade (ficaria de outra UF) e o cache de municípios. */
    public function updatedFilialEstado(): void
    {
        $this->filial_cidade = '';
        unset($this->municipiosDaFilial);
    }

    public function abrirFormFilial(?int $filialId = null): void
    {
        $this->resetValidation();
        $this->reset([
            'filial_nome', 'filial_cnpj', 'filial_inscricao_estadual',
            'filial_telefone', 'filial_cidade', 'filial_estado', 'filial_ativo',
        ]);
        $this->filialEditandoId = $filialId;

        if ($filialId !== null) {
            $filial = $this->localizarFilial($filialId);
            $this->filial_nome = $filial->nome;
            $this->filial_cnpj = (string) $filial->cnpj;
            $this->filial_inscricao_estadual = (string) $filial->inscricao_estadual;
            $this->filial_telefone = (string) $filial->telefone;
            $this->filial_cidade = (string) $filial->cidade;
            $this->filial_estado = (string) $filial->estado;
            $this->filial_ativo = (bool) $filial->ativo;
        }

        $this->mostrarFormFilial = true;
    }

    public function cancelarFormFilial(): void
    {
        $this->mostrarFormFilial = false;
        $this->filialEditandoId = null;
        $this->resetValidation();
    }

    public function salvarFilial(CriarFilialAction $criar, AtualizarFilialAction $atualizar): void
    {
        if ($this->empresaId === null) {
            return;
        }

        $empresa = Empresa::findOrFail($this->empresaId);
        $this->authorize('update', $empresa);

        $dados = $this->validate($this->regrasFilial(), attributes: [
            'filial_nome' => 'nome',
            'filial_cnpj' => 'CNPJ',
            'filial_inscricao_estadual' => 'inscrição estadual',
            'filial_telefone' => 'telefone',
            'filial_cidade' => 'cidade',
            'filial_estado' => 'UF',
        ]);

        $dto = FilialDTO::fromArray([
            'nome' => $dados['filial_nome'],
            'cnpj' => $dados['filial_cnpj'],
            'inscricao_estadual' => $dados['filial_inscricao_estadual'],
            'telefone' => $dados['filial_telefone'],
            'cidade' => $dados['filial_cidade'],
            'estado' => $dados['filial_estado'],
            'ativo' => $dados['filial_ativo'],
        ]);

        try {
            if ($this->filialEditandoId === null) {
                $criar->execute($empresa, $dto);
                $this->notificarSucesso('Filial criada.');
            } else {
                $atualizar->execute($this->localizarFilial($this->filialEditandoId), $dto);
                $this->notificarSucesso('Filial atualizada.');
            }
        } catch (FilialException $e) {
            $this->notificarErro($e->getMessage());

            return;
        }

        unset($this->filiais);
        $this->cancelarFormFilial();
    }

    public function alternarFiliaisLixeira(): void
    {
        $this->verFiliaisLixeira = ! $this->verFiliaisLixeira;
        $this->cancelarFormFilial();
        unset($this->filiais);
    }

    public function solicitarExcluirFilial(int $filialId): void
    {
        if ($this->empresaId === null) {
            return;
        }

        $this->authorize('update', Empresa::findOrFail($this->empresaId));

        if ($this->localizarFilial($filialId)->e_matriz) {
            $this->notificarErro('A Matriz não pode ser excluída.');

            return;
        }

        $this->dispatch(
            'confirm',
            title: 'Mover filial para a lixeira?',
            text: 'A filial sai da lista, mas pode ser restaurada da lixeira.',
            destructive: true,
            onConfirm: 'empresas::filial-excluir',
            params: ['id' => $filialId],
        );
    }

    #[On('empresas::filial-excluir')]
    public function excluirFilial(int $id): void
    {
        if ($this->empresaId === null) {
            return;
        }

        $this->authorize('update', Empresa::findOrFail($this->empresaId));

        $filial = $this->localizarFilial($id);

        if ($filial->e_matriz) {
            $this->notificarErro('A Matriz não pode ser excluída.');

            return;
        }

        $filial->delete();
        unset($this->filiais);
        $this->notificarSucesso('Filial movida para a lixeira.');
    }

    public function solicitarRestaurarFilial(int $filialId): void
    {
        if ($this->empresaId === null) {
            return;
        }

        $this->authorize('restore', Empresa::findOrFail($this->empresaId));

        $this->dispatch(
            'confirm',
            title: 'Restaurar filial?',
            text: 'A filial volta para a lista de ativas.',
            destructive: false,
            onConfirm: 'empresas::filial-restaurar',
            params: ['id' => $filialId],
        );
    }

    #[On('empresas::filial-restaurar')]
    public function restaurarFilial(int $id): void
    {
        if ($this->empresaId === null) {
            return;
        }

        $this->authorize('restore', Empresa::findOrFail($this->empresaId));

        $this->localizarFilialNaLixeira($id)->restore();
        unset($this->filiais);
        $this->notificarSucesso('Filial restaurada.');
    }

    public function solicitarExcluirDefinitivoFilial(int $filialId): void
    {
        if ($this->empresaId === null) {
            return;
        }

        $this->authorize('forceDelete', Empresa::findOrFail($this->empresaId));

        $this->dispatch(
            'confirm',
            title: 'Excluir filial definitivamente?',
            text: 'Ação irreversível: a filial é removida do banco de dados.',
            destructive: true,
            confirmText: 'Sim, excluir definitivamente',
            onConfirm: 'empresas::filial-force',
            params: ['id' => $filialId],
        );
    }

    #[On('empresas::filial-force')]
    public function excluirDefinitivoFilial(int $id): void
    {
        if ($this->empresaId === null) {
            return;
        }

        $this->authorize('forceDelete', Empresa::findOrFail($this->empresaId));

        $this->localizarFilialNaLixeira($id)->forceDelete();
        unset($this->filiais);
        $this->notificarSucesso('Filial excluída definitivamente.');
    }

    public function render(): View
    {
        return view('livewire.admin.empresas.form-empresa', [
            'modo' => $this->empresaId === null ? 'criar' : 'editar',
        ])->title($this->empresaId === null ? 'Nova empresa' : 'Editar empresa');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $hex = ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'nome' => ['required', 'string', 'min:2', 'max:120'],
            'razao_social' => ['nullable', 'string', 'max:191'],
            'cnpj' => ['nullable', 'string', 'max:18', Rule::unique('empresas', 'cnpj')->ignore($this->empresaId)],
            'inscricao_estadual' => ['nullable', 'string', 'max:30'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:191'],
            'site_url' => ['nullable', 'string', 'max:191'],
            'cor_primaria' => $hex,
            'cor_secundaria' => $hex,
            'cor_sucesso' => $hex,
            'cor_warning' => $hex,
            'cor_perigo' => $hex,
            'cor_info' => $hex,
            'ativo' => ['boolean'],
        ];
    }

    protected function localizarFilial(int $filialId): Filial
    {
        return Filial::query()
            ->where('empresa_id', $this->empresaId)
            ->findOrFail($filialId);
    }

    protected function localizarFilialNaLixeira(int $filialId): Filial
    {
        return Filial::query()
            ->onlyTrashed()
            ->where('empresa_id', $this->empresaId)
            ->findOrFail($filialId);
    }

    /**
     * @return array<string, mixed>
     */
    protected function regrasFilial(): array
    {
        return [
            'filial_nome' => ['required', 'string', 'min:2', 'max:120'],
            'filial_cnpj' => ['nullable', 'string', 'max:18'],
            'filial_inscricao_estadual' => ['nullable', 'string', 'max:30'],
            'filial_telefone' => ['nullable', 'string', 'max:20'],
            'filial_cidade' => ['nullable', 'string', 'max:120'],
            'filial_estado' => ['nullable', 'string', 'size:2'],
            'filial_ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'razao_social' => 'razão social',
            'inscricao_estadual' => 'inscrição estadual',
            'site_url' => 'site',
            'cor_primaria' => 'cor primária',
            'cor_secundaria' => 'cor secundária',
            'cor_sucesso' => 'cor de sucesso',
            'cor_warning' => 'cor de alerta',
            'cor_perigo' => 'cor de perigo',
            'cor_info' => 'cor de informação',
        ];
    }
}
