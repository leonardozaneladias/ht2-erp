<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Actions\Admin\Referencia\CreateMoedaAction;
use App\Actions\Admin\Referencia\UpdateMoedaAction;
use App\DTOs\Admin\Referencia\MoedaDTO;
use App\Http\Requests\Admin\Referencia\MoedaRules;
use App\Models\Referencia\Moeda;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormMoeda extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $moedaId = null;

    public string $codigo_iso = '';

    public string $numerico = '';

    public string $nome = '';

    public string $simbolo = '';

    public int $casas_decimais = 2;

    public bool $ativo = true;

    public function mount(?int $moeda = null): void
    {
        if ($moeda !== null) {
            $registro = Moeda::findOrFail($moeda);
            $this->authorize('update', $registro);

            $this->moedaId = $registro->id;
            $this->codigo_iso = (string) $registro->codigo_iso;
            $this->numerico = (string) $registro->numerico;
            $this->nome = (string) $registro->nome;
            $this->simbolo = (string) $registro->simbolo;
            $this->casas_decimais = (int) $registro->casas_decimais;
            $this->ativo = (bool) $registro->ativo;

            return;
        }

        $this->authorize('create', Moeda::class);
    }

    public function salvar(CreateMoedaAction $criar, UpdateMoedaAction $atualizar): void
    {
        $dados = $this->validate(
            MoedaRules::regras($this->moedaId),
            attributes: $this->validationAttributes(),
        );

        $dto = MoedaDTO::fromArray($dados);

        if ($this->moedaId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'Moeda criada com sucesso.');
        } else {
            $atualizar->execute(Moeda::findOrFail($this->moedaId), $dto);
            $this->notificarAposRedirect('success', 'Moeda atualizada com sucesso.');
        }

        $this->redirect(route('admin.referencia.moedas.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.moedas.form-moeda', [
            'modo' => $this->moedaId === null ? 'criar' : 'editar',
        ])->title($this->moedaId === null ? 'Nova moeda' : 'Editar moeda');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'codigo_iso' => 'código ISO',
            'numerico' => 'código numérico',
            'nome' => 'nome',
            'simbolo' => 'símbolo',
            'casas_decimais' => 'casas decimais',
            'ativo' => 'ativo',
        ];
    }
}
