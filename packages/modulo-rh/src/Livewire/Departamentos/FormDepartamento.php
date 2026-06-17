<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Livewire\Departamentos;

use App\Livewire\Concerns\EmiteNotificacoes;
use HT2ERP\Rh\Actions\CreateDepartamentoAction;
use HT2ERP\Rh\Actions\UpdateDepartamentoAction;
use HT2ERP\Rh\DTOs\DepartamentoDTO;
use HT2ERP\Rh\Http\Requests\DepartamentoRules;
use HT2ERP\Rh\Models\Departamento;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormDepartamento extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $departamentoId = null;

    public string $nome = '';

    public string $sigla = '';

    public string $status = 'ativo';

    public function mount(?int $departamento = null): void
    {
        if ($departamento !== null) {
            $registro = Departamento::findOrFail($departamento);
            $this->authorize('update', $registro);

            $this->departamentoId = $registro->id;
            $this->nome = (string) $registro->nome;
            $this->sigla = (string) $registro->sigla;
            $this->status = $registro->status->value;

            return;
        }

        $this->authorize('create', Departamento::class);
    }

    public function salvar(CreateDepartamentoAction $criar, UpdateDepartamentoAction $atualizar): void
    {
        $dados = $this->validate(
            DepartamentoRules::regras($this->departamentoId),
            attributes: $this->validationAttributes(),
        );

        $dto = DepartamentoDTO::fromArray($dados);

        if ($this->departamentoId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'Departamento criado(a) com sucesso.');
        } else {
            $atualizar->execute(Departamento::findOrFail($this->departamentoId), $dto);
            $this->notificarAposRedirect('success', 'Departamento atualizado(a) com sucesso.');
        }

        $this->redirect(route('admin.rh.departamentos.index'), navigate: true);
    }

    public function render(): View
    {
        return view('rh::livewire.departamentos.form-departamento', [
            'modo' => $this->departamentoId === null ? 'criar' : 'editar',
        ])->title($this->departamentoId === null ? 'Novo registro' : 'Editar registro');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nome' => 'nome',
            'sigla' => 'sigla',
            'status' => 'status',
        ];
    }
}
