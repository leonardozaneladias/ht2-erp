<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Empresas;

use App\Actions\Admin\AtualizarFilialAction;
use App\Actions\Admin\CreateEmpresaAction;
use App\Actions\Admin\CriarFilialAction;
use App\Actions\Admin\UpdateEmpresaAction;
use App\DTOs\Admin\EmpresaDTO;
use App\DTOs\Admin\FilialDTO;
use App\Exceptions\FilialException;
use App\Livewire\Concerns\EmiteNotificacoes;
use App\Models\Empresa;
use App\Models\Filial;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
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

        return Filial::query()
            ->where('empresa_id', $this->empresaId)
            ->orderByDesc('e_matriz')
            ->orderBy('nome')
            ->get();
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
