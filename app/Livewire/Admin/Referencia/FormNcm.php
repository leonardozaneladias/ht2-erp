<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Actions\Admin\Referencia\CreateNcmAction;
use App\Actions\Admin\Referencia\UpdateNcmAction;
use App\DTOs\Admin\Referencia\NcmDTO;
use App\Http\Requests\Admin\Referencia\NcmRules;
use App\Livewire\Concerns\EmiteNotificacoes;
use App\Models\Referencia\Ncm;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormNcm extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $ncmId = null;

    public string $codigo = '';

    public string $descricao = '';

    public function mount(?int $ncm = null): void
    {
        if ($ncm !== null) {
            $registro = Ncm::findOrFail($ncm);
            $this->authorize('update', $registro);

            $this->ncmId = $registro->id;
            $this->codigo = (string) $registro->codigo;
            $this->descricao = (string) $registro->descricao;

            return;
        }

        $this->authorize('create', Ncm::class);
    }

    public function salvar(CreateNcmAction $criar, UpdateNcmAction $atualizar): void
    {
        $dados = $this->validate(
            NcmRules::regras($this->ncmId),
            attributes: $this->validationAttributes(),
        );

        $dto = NcmDTO::fromArray($dados);

        if ($this->ncmId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'NCM criado com sucesso.');
        } else {
            $atualizar->execute(Ncm::findOrFail($this->ncmId), $dto);
            $this->notificarAposRedirect('success', 'NCM atualizado com sucesso.');
        }

        $this->redirect(route('admin.referencia.ncms.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.ncms.form-ncm', [
            'modo' => $this->ncmId === null ? 'criar' : 'editar',
        ])->title($this->ncmId === null ? 'Novo NCM' : 'Editar NCM');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'codigo' => 'código',
            'descricao' => 'descrição',
        ];
    }
}
