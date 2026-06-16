<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Actions\Admin\Referencia\CreateBancoAction;
use App\Actions\Admin\Referencia\UpdateBancoAction;
use App\DTOs\Admin\Referencia\BancoDTO;
use App\Http\Requests\Admin\Referencia\BancoRules;
use App\Livewire\Concerns\EmiteNotificacoes;
use App\Models\Referencia\Banco;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormBanco extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $bancoId = null;

    public string $ispb = '';

    public string $codigo_compe = '';

    public string $nome = '';

    public string $nome_completo = '';

    public bool $ativo = true;

    public function mount(?int $banco = null): void
    {
        if ($banco !== null) {
            $registro = Banco::findOrFail($banco);
            $this->authorize('update', $registro);

            $this->bancoId = $registro->id;
            $this->ispb = (string) $registro->ispb;
            $this->codigo_compe = (string) $registro->codigo_compe;
            $this->nome = (string) $registro->nome;
            $this->nome_completo = (string) $registro->nome_completo;
            $this->ativo = (bool) $registro->ativo;

            return;
        }

        $this->authorize('create', Banco::class);
    }

    public function salvar(CreateBancoAction $criar, UpdateBancoAction $atualizar): void
    {
        $dados = $this->validate(
            BancoRules::regras($this->bancoId),
            attributes: $this->validationAttributes(),
        );

        $dto = BancoDTO::fromArray($dados);

        if ($this->bancoId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'Banco criado com sucesso.');
        } else {
            $atualizar->execute(Banco::findOrFail($this->bancoId), $dto);
            $this->notificarAposRedirect('success', 'Banco atualizado com sucesso.');
        }

        $this->redirect(route('admin.referencia.bancos.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.bancos.form-banco', [
            'modo' => $this->bancoId === null ? 'criar' : 'editar',
        ])->title($this->bancoId === null ? 'Novo banco' : 'Editar banco');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'ispb' => 'ISPB',
            'codigo_compe' => 'código COMPE',
            'nome' => 'nome',
            'nome_completo' => 'nome completo',
            'ativo' => 'ativo',
        ];
    }
}
