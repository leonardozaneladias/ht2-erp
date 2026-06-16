<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Actions\Admin\Referencia\CreateCnaeAction;
use App\Actions\Admin\Referencia\UpdateCnaeAction;
use App\DTOs\Admin\Referencia\CnaeDTO;
use App\Http\Requests\Admin\Referencia\CnaeRules;
use App\Livewire\Concerns\EmiteNotificacoes;
use App\Models\Referencia\Cnae;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormCnae extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $cnaeId = null;

    public string $codigo = '';

    public string $descricao = '';

    public ?string $secao = null;

    public ?string $divisao = null;

    public ?string $grupo = null;

    public ?string $classe = null;

    public function mount(?int $cnae = null): void
    {
        if ($cnae !== null) {
            $registro = Cnae::findOrFail($cnae);
            $this->authorize('update', $registro);

            $this->cnaeId = $registro->id;
            $this->codigo = (string) $registro->codigo;
            $this->descricao = (string) $registro->descricao;
            $this->secao = $registro->secao;
            $this->divisao = $registro->divisao;
            $this->grupo = $registro->grupo;
            $this->classe = $registro->classe;

            return;
        }

        $this->authorize('create', Cnae::class);
    }

    public function salvar(CreateCnaeAction $criar, UpdateCnaeAction $atualizar): void
    {
        $dados = $this->validate(
            CnaeRules::regras($this->cnaeId),
            attributes: $this->validationAttributes(),
        );

        $dto = CnaeDTO::fromArray($dados);

        if ($this->cnaeId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'CNAE criado com sucesso.');
        } else {
            $atualizar->execute(Cnae::findOrFail($this->cnaeId), $dto);
            $this->notificarAposRedirect('success', 'CNAE atualizado com sucesso.');
        }

        $this->redirect(route('admin.referencia.cnaes.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.cnaes.form-cnae', [
            'modo' => $this->cnaeId === null ? 'criar' : 'editar',
        ])->title($this->cnaeId === null ? 'Novo CNAE' : 'Editar CNAE');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'codigo' => 'código',
            'descricao' => 'descrição',
            'secao' => 'seção',
            'divisao' => 'divisão',
            'grupo' => 'grupo',
            'classe' => 'classe',
        ];
    }
}
