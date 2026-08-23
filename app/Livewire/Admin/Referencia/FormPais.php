<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Actions\Admin\Referencia\CreatePaisAction;
use App\Actions\Admin\Referencia\UpdatePaisAction;
use App\DTOs\Admin\Referencia\PaisDTO;
use App\Http\Requests\Admin\Referencia\PaisRules;
use App\Models\Referencia\Pais;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormPais extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $paisId = null;

    public string $codigo_iso2 = '';

    public ?string $codigo_iso3 = null;

    public ?string $codigo_numerico = null;

    public string $nome = '';

    public bool $ativo = true;

    public function mount(?int $pais = null): void
    {
        if ($pais !== null) {
            $registro = Pais::findOrFail($pais);
            $this->authorize('update', $registro);

            $this->paisId = $registro->id;
            $this->codigo_iso2 = (string) $registro->codigo_iso2;
            $this->codigo_iso3 = $registro->codigo_iso3;
            $this->codigo_numerico = $registro->codigo_numerico;
            $this->nome = (string) $registro->nome;
            $this->ativo = (bool) $registro->ativo;

            return;
        }

        $this->authorize('create', Pais::class);
    }

    public function salvar(CreatePaisAction $criar, UpdatePaisAction $atualizar): void
    {
        $dados = $this->validate(
            PaisRules::regras($this->paisId),
            attributes: $this->validationAttributes(),
        );

        $dto = PaisDTO::fromArray($dados);

        if ($this->paisId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'País criado com sucesso.');
        } else {
            $atualizar->execute(Pais::findOrFail($this->paisId), $dto);
            $this->notificarAposRedirect('success', 'País atualizado com sucesso.');
        }

        $this->redirect(route('admin.referencia.paises.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.paises.form-pais', [
            'modo' => $this->paisId === null ? 'criar' : 'editar',
        ])->title($this->paisId === null ? 'Novo país' : 'Editar país');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'codigo_iso2' => 'código ISO2',
            'codigo_iso3' => 'código ISO3',
            'codigo_numerico' => 'código numérico',
            'nome' => 'nome',
            'ativo' => 'ativo',
        ];
    }
}
