<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Configuracao;

use App\Actions\Admin\Settings\SaveLoginSettingsAction;
use HT2ML\Core\DTOs\Admin\Settings\LoginSettingsDTO;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use HT2ML\Core\Services\Admin\Settings\SettingsFileUploadService;
use HT2ML\Core\Settings\LoginSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Aba "Tela de login": textos, imagem de fundo e elementos visíveis na
 * página pública de autenticação.
 */
class AbaLogin extends Component
{
    use EmiteNotificacoes;
    use WithFileUploads;

    public string $titulo = '';

    public string $subtitulo = '';

    public bool $mostrar_logo = true;

    public bool $mostrar_versao = false;

    /** @var \Illuminate\Http\UploadedFile|null */
    public $fundo = null;

    public function mount(LoginSettings $settings): void
    {
        $this->titulo = $settings->titulo;
        $this->subtitulo = $settings->subtitulo;
        $this->mostrar_logo = $settings->mostrar_logo;
        $this->mostrar_versao = $settings->mostrar_versao;
    }

    public function salvar(
        SaveLoginSettingsAction $action,
        SettingsFileUploadService $uploads,
        LoginSettings $settings,
    ): void {
        $this->validate();

        $fundoCaminho = $this->fundo !== null
            ? $uploads->substituir($this->fundo, $settings->fundo_imagem_arquivo, 'login')
            : null;

        $action->execute(new LoginSettingsDTO(
            titulo: $this->titulo,
            subtitulo: $this->subtitulo,
            mostrar_logo: $this->mostrar_logo,
            mostrar_versao: $this->mostrar_versao,
            fundo_imagem_arquivo: $fundoCaminho,
        ));

        $this->reset('fundo');

        $this->notificarSucesso('Tela de login atualizada.');
    }

    public function render(LoginSettings $settings): View
    {
        return view('livewire.admin.configuracao.aba-login', [
            'fundoAtual' => $settings->fundo_imagem_arquivo !== ''
                ? Storage::disk('public')->url($settings->fundo_imagem_arquivo)
                : null,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:120'],
            'subtitulo' => ['nullable', 'string', 'max:200'],
            'mostrar_logo' => ['boolean'],
            'mostrar_versao' => ['boolean'],
            'fundo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'titulo' => 'título',
            'subtitulo' => 'subtítulo',
            'fundo' => 'imagem de fundo',
        ];
    }
}
