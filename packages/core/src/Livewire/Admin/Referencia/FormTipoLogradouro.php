<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Actions\Admin\Referencia\CreateTipoLogradouroAction;
use HT2ML\Core\Actions\Admin\Referencia\UpdateTipoLogradouroAction;
use HT2ML\Core\DTOs\Admin\Referencia\TipoLogradouroDTO;
use HT2ML\Core\Http\Requests\Admin\Referencia\TipoLogradouroRules;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use HT2ML\Core\Models\Referencia\TipoLogradouro;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormTipoLogradouro extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $tipoLogradouroId = null;

    public string $nome = '';

    public ?string $codigo = null;

    public ?string $abrev = null;

    public bool $ativo = true;

    public function mount(?int $tipo_logradouro = null): void
    {
        if ($tipo_logradouro !== null) {
            $registro = TipoLogradouro::findOrFail($tipo_logradouro);
            $this->authorize('update', $registro);

            $this->tipoLogradouroId = $registro->id;
            $this->nome = (string) $registro->nome;
            $this->codigo = $registro->codigo;
            $this->abrev = $registro->abrev;
            $this->ativo = (bool) $registro->ativo;

            return;
        }

        $this->authorize('create', TipoLogradouro::class);
    }

    public function salvar(CreateTipoLogradouroAction $criar, UpdateTipoLogradouroAction $atualizar): void
    {
        $dados = $this->validate(
            TipoLogradouroRules::regras($this->tipoLogradouroId),
            attributes: $this->validationAttributes(),
        );

        $dto = TipoLogradouroDTO::fromArray($dados);

        if ($this->tipoLogradouroId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'Tipo de logradouro criado com sucesso.');
        } else {
            $atualizar->execute(TipoLogradouro::findOrFail($this->tipoLogradouroId), $dto);
            $this->notificarAposRedirect('success', 'Tipo de logradouro atualizado com sucesso.');
        }

        $this->redirect(route('admin.referencia.tipos_logradouro.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.tipos_logradouro.form-tipo-logradouro', [
            'modo' => $this->tipoLogradouroId === null ? 'criar' : 'editar',
        ])->title($this->tipoLogradouroId === null ? 'Novo tipo de logradouro' : 'Editar tipo de logradouro');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nome' => 'nome',
            'codigo' => 'código',
            'abrev' => 'abreviação',
            'ativo' => 'ativo',
        ];
    }
}
