<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Actions\Admin\Referencia\CreateCfopAction;
use App\Actions\Admin\Referencia\UpdateCfopAction;
use App\DTOs\Admin\Referencia\CfopDTO;
use App\Enums\Referencia\TipoCfop;
use App\Http\Requests\Admin\Referencia\CfopRules;
use App\Livewire\Concerns\EmiteNotificacoes;
use App\Models\Referencia\Cfop;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormCfop extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $cfopId = null;

    public string $codigo = '';

    public string $descricao = '';

    public string $tipo = '';

    public string $aplicacao = '';

    public function mount(?int $cfop = null): void
    {
        if ($cfop !== null) {
            $registro = Cfop::findOrFail($cfop);
            $this->authorize('update', $registro);

            $this->cfopId = $registro->id;
            $this->codigo = (string) $registro->codigo;
            $this->descricao = (string) $registro->descricao;
            $this->tipo = $registro->tipo->value;
            $this->aplicacao = (string) $registro->aplicacao;

            return;
        }

        $this->authorize('create', Cfop::class);
    }

    public function salvar(CreateCfopAction $criar, UpdateCfopAction $atualizar): void
    {
        $dados = $this->validate(
            CfopRules::regras($this->cfopId),
            attributes: $this->validationAttributes(),
        );

        $dto = CfopDTO::fromArray($dados);

        if ($this->cfopId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'CFOP criado com sucesso.');
        } else {
            $atualizar->execute(Cfop::findOrFail($this->cfopId), $dto);
            $this->notificarAposRedirect('success', 'CFOP atualizado com sucesso.');
        }

        $this->redirect(route('admin.referencia.cfops.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.cfops.form-cfop', [
            'modo' => $this->cfopId === null ? 'criar' : 'editar',
            'tipos' => $this->opcoesTipo(),
        ])->title($this->cfopId === null ? 'Novo CFOP' : 'Editar CFOP');
    }

    /**
     * @return array<string, string>
     */
    protected function opcoesTipo(): array
    {
        $opcoes = [];

        foreach (TipoCfop::cases() as $tipo) {
            $opcoes[$tipo->value] = $tipo->label();
        }

        return $opcoes;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'codigo' => 'código',
            'descricao' => 'descrição',
            'tipo' => 'tipo',
            'aplicacao' => 'aplicação',
        ];
    }
}
