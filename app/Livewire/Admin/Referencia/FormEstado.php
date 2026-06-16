<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Actions\Admin\Referencia\CreateEstadoAction;
use App\Actions\Admin\Referencia\UpdateEstadoAction;
use App\DTOs\Admin\Referencia\EstadoDTO;
use App\Enums\Referencia\RegiaoBrasil;
use App\Http\Requests\Admin\Referencia\EstadoRules;
use App\Livewire\Concerns\EmiteNotificacoes;
use App\Models\Referencia\Estado;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormEstado extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $estadoId = null;

    public string $codigo_ibge = '';

    public string $sigla = '';

    public string $nome = '';

    public string $regiao = '';

    public function mount(?int $estado = null): void
    {
        if ($estado !== null) {
            $registro = Estado::findOrFail($estado);
            $this->authorize('update', $registro);

            $this->estadoId = $registro->id;
            $this->codigo_ibge = (string) $registro->codigo_ibge;
            $this->sigla = (string) $registro->sigla;
            $this->nome = (string) $registro->nome;
            $this->regiao = $registro->regiao->value;

            return;
        }

        $this->authorize('create', Estado::class);
    }

    public function salvar(CreateEstadoAction $criar, UpdateEstadoAction $atualizar): void
    {
        $dados = $this->validate(
            EstadoRules::regras($this->estadoId),
            attributes: $this->validationAttributes(),
        );

        $dto = EstadoDTO::fromArray($dados);

        if ($this->estadoId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'Estado criado com sucesso.');
        } else {
            $atualizar->execute(Estado::findOrFail($this->estadoId), $dto);
            $this->notificarAposRedirect('success', 'Estado atualizado com sucesso.');
        }

        $this->redirect(route('admin.referencia.estados.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.estados.form-estado', [
            'modo' => $this->estadoId === null ? 'criar' : 'editar',
            'regioes' => $this->opcoesRegiao(),
        ])->title($this->estadoId === null ? 'Novo estado' : 'Editar estado');
    }

    /**
     * @return array<string, string>
     */
    protected function opcoesRegiao(): array
    {
        $opcoes = [];

        foreach (RegiaoBrasil::cases() as $regiao) {
            $opcoes[$regiao->value] = $regiao->label();
        }

        return $opcoes;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'codigo_ibge' => 'código IBGE',
            'sigla' => 'sigla',
            'nome' => 'nome',
            'regiao' => 'região',
        ];
    }
}
