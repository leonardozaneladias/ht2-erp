<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Actions\Admin\Referencia\CreateMunicipioAction;
use App\Actions\Admin\Referencia\UpdateMunicipioAction;
use App\DTOs\Admin\Referencia\MunicipioDTO;
use App\Http\Requests\Admin\Referencia\MunicipioRules;
use App\Models\Referencia\Estado;
use App\Models\Referencia\Municipio;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormMunicipio extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $municipioId = null;

    public string $codigo_ibge = '';

    public string $nome = '';

    public ?int $estado_id = null;

    public function mount(?int $municipio = null): void
    {
        if ($municipio !== null) {
            $registro = Municipio::findOrFail($municipio);
            $this->authorize('update', $registro);

            $this->municipioId = $registro->id;
            $this->codigo_ibge = (string) $registro->codigo_ibge;
            $this->nome = (string) $registro->nome;
            $this->estado_id = (int) $registro->estado_id;

            return;
        }

        $this->authorize('create', Municipio::class);
    }

    public function salvar(CreateMunicipioAction $criar, UpdateMunicipioAction $atualizar): void
    {
        $dados = $this->validate(
            MunicipioRules::regras($this->municipioId),
            attributes: $this->validationAttributes(),
        );

        $dto = MunicipioDTO::fromArray($dados);

        if ($this->municipioId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'Município criado com sucesso.');
        } else {
            $atualizar->execute(Municipio::findOrFail($this->municipioId), $dto);
            $this->notificarAposRedirect('success', 'Município atualizado com sucesso.');
        }

        $this->redirect(route('admin.referencia.municipios.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.municipios.form-municipio', [
            'modo' => $this->municipioId === null ? 'criar' : 'editar',
            'estados' => $this->opcoesEstado(),
        ])->title($this->municipioId === null ? 'Novo município' : 'Editar município');
    }

    /**
     * Estados (UF) ordenados por nome — opções do select de estado.
     *
     * @return array<int, string>
     */
    protected function opcoesEstado(): array
    {
        return Estado::query()
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'codigo_ibge' => 'código IBGE',
            'nome' => 'nome',
            'estado_id' => 'estado',
        ];
    }
}
