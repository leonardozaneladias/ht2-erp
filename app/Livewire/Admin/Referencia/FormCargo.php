<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Actions\Admin\Referencia\CreateCargoAction;
use App\Actions\Admin\Referencia\UpdateCargoAction;
use App\DTOs\Admin\Referencia\CargoDTO;
use App\Http\Requests\Admin\Referencia\CargoRules;
use App\Models\Referencia\Cargo;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormCargo extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $cargoId = null;

    public string $codigo_cbo = '';

    public string $descricao = '';

    public bool $ativo = true;

    public function mount(?int $cargo = null): void
    {
        if ($cargo !== null) {
            $registro = Cargo::findOrFail($cargo);
            $this->authorize('update', $registro);

            $this->cargoId = $registro->id;
            $this->codigo_cbo = (string) $registro->codigo_cbo;
            $this->descricao = (string) $registro->descricao;
            $this->ativo = (bool) $registro->ativo;

            return;
        }

        $this->authorize('create', Cargo::class);
    }

    public function salvar(CreateCargoAction $criar, UpdateCargoAction $atualizar): void
    {
        $dados = $this->validate(
            CargoRules::regras($this->cargoId),
            attributes: $this->validationAttributes(),
        );

        $dto = CargoDTO::fromArray($dados);

        if ($this->cargoId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'Cargo criado com sucesso.');
        } else {
            $atualizar->execute(Cargo::findOrFail($this->cargoId), $dto);
            $this->notificarAposRedirect('success', 'Cargo atualizado com sucesso.');
        }

        $this->redirect(route('admin.referencia.cargos.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.cargos.form-cargo', [
            'modo' => $this->cargoId === null ? 'criar' : 'editar',
        ])->title($this->cargoId === null ? 'Novo cargo' : 'Editar cargo');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'codigo_cbo' => 'código CBO',
            'descricao' => 'descrição',
            'ativo' => 'ativo',
        ];
    }
}
