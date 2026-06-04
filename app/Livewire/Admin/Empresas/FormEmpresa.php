<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Empresas;

use App\Actions\Admin\CreateEmpresaAction;
use App\Actions\Admin\UpdateEmpresaAction;
use App\DTOs\Admin\EmpresaDTO;
use App\Models\Empresa;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormEmpresa extends Component
{
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
            session()->flash('toast.success', 'Empresa criada.');
        } else {
            $atualizar->execute(Empresa::findOrFail($this->empresaId), $dto);
            session()->flash('toast.success', 'Empresa atualizada.');
        }

        $this->redirect(route('admin.empresas.index'), navigate: true);
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
