<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Shared;

use App\Models\Anexo;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class GerenciadorAnexos extends Component
{
    use WithFileUploads;

    public int $modelId;

    public string $modelType;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $arquivo = null;

    /** @var list<array{id: int, nome_original: string, tamanho_formatado: string, url: string}> */
    public array $anexos = [];

    public function mount(int $modelId, string $modelType): void
    {
        $this->modelId = $modelId;
        $this->modelType = $modelType;
        $this->carregarAnexos();
    }

    public function uploadAnexo(): void
    {
        $this->validate(['arquivo' => 'required|file|max:20480']);

        $caminho = $this->arquivo->store('anexos', 'public');

        if ($caminho === false) {
            $this->dispatch('toast', variant: 'danger', message: 'Erro ao armazenar o arquivo.');

            return;
        }

        $empresaId = app(TenantContext::class)->empresaAtivaId();

        Anexo::create([
            'empresa_id' => $empresaId,
            'anexavel_type' => $this->modelType,
            'anexavel_id' => $this->modelId,
            'disco' => 'public',
            'caminho' => $caminho,
            'nome_original' => $this->arquivo->getClientOriginalName(),
            'mime' => $this->arquivo->getMimeType() ?? 'application/octet-stream',
            'tamanho' => $this->arquivo->getSize(),
        ]);

        $this->arquivo = null;
        $this->carregarAnexos();
        $this->dispatch('toast', variant: 'success', message: 'Anexo adicionado.');
    }

    public function solicitarRemoverAnexo(int $id): void
    {
        $this->dispatch(
            'confirm',
            title: 'Remover este anexo?',
            text: 'O arquivo será excluído permanentemente.',
            destructive: true,
            onConfirm: 'anexos::remover',
            params: ['id' => $id],
        );
    }

    #[\Livewire\Attributes\On('anexos::remover')]
    public function removerAnexo(int $id): void
    {
        $anexo = Anexo::findOrFail($id);
        Storage::disk($anexo->disco)->delete($anexo->caminho);
        $anexo->delete();
        $this->carregarAnexos();
        $this->dispatch('toast', variant: 'success', message: 'Anexo removido.');
    }

    public function render(): View
    {
        return view('livewire.admin.shared.gerenciador-anexos');
    }

    private function carregarAnexos(): void
    {
        $this->anexos = Anexo::where('anexavel_id', $this->modelId)
            ->where('anexavel_type', $this->modelType)
            ->get()
            ->map(fn (Anexo $a) => [
                'id' => $a->id,
                'nome_original' => $a->nome_original,
                'tamanho_formatado' => $a->tamanhoFormatado(),
                'url' => $a->url(),
            ])
            ->values()
            ->all();
    }
}
