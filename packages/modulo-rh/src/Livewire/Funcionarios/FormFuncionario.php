<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Livewire\Funcionarios;

use App\Livewire\Concerns\EmiteNotificacoes;
use App\Models\Referencia\Cargo;
use HT2ERP\Rh\Actions\CreateFuncionarioAction;
use HT2ERP\Rh\Actions\UpdateFuncionarioAction;
use HT2ERP\Rh\DTOs\FuncionarioDTO;
use HT2ERP\Rh\Http\Requests\FuncionarioRules;
use HT2ERP\Rh\Models\Funcionario;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormFuncionario extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $funcionarioId = null;

    public string $nome = '';

    public string $cpf = '';

    public string $cargo = '';

    public int $salario = 0;

    public string $admissao = '';

    public string $status = 'ativo';

    public function mount(?int $funcionario = null): void
    {
        if ($funcionario !== null) {
            $registro = Funcionario::findOrFail($funcionario);
            $this->authorize('update', $registro);

            $this->funcionarioId = $registro->id;
            $this->nome = (string) $registro->nome;
            $this->cpf = (string) $registro->cpf;
            $this->cargo = (string) $registro->cargo;
            $this->salario = (int) $registro->salario;
            $this->admissao = $registro->admissao->format('Y-m-d');
            $this->status = $registro->status->value;

            return;
        }

        $this->authorize('create', Funcionario::class);
    }

    public function salvar(CreateFuncionarioAction $criar, UpdateFuncionarioAction $atualizar): void
    {
        $dados = $this->validate(
            FuncionarioRules::regras($this->funcionarioId),
            attributes: $this->validationAttributes(),
        );

        $dto = FuncionarioDTO::fromArray($dados);

        if ($this->funcionarioId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'Funcionario criado(a) com sucesso.');
        } else {
            $atualizar->execute(Funcionario::findOrFail($this->funcionarioId), $dto);
            $this->notificarAposRedirect('success', 'Funcionario atualizado(a) com sucesso.');
        }

        $this->redirect(route('admin.rh.funcionarios.index'), navigate: true);
    }

    /**
     * Cargos do catálogo (descrição => descrição) + o valor atual se fora do catálogo.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function cargosDisponiveis(): array
    {
        $cargos = Cargo::query()->where('ativo', true)->orderBy('descricao')->pluck('descricao')->all();

        if ($this->cargo !== '' && ! in_array($this->cargo, $cargos, true)) {
            array_unshift($cargos, $this->cargo);
        }

        return array_combine($cargos, $cargos);
    }

    public function render(): View
    {
        return view('rh::livewire.funcionarios.form-funcionario', [
            'modo' => $this->funcionarioId === null ? 'criar' : 'editar',
        ])->title($this->funcionarioId === null ? 'Novo registro' : 'Editar registro');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nome' => 'nome',
            'cpf' => 'cpf',
            'cargo' => 'cargo',
            'salario' => 'salario',
            'admissao' => 'admissao',
            'status' => 'status',
        ];
    }
}
