<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Configuracao;

use App\Actions\Admin\Settings\SaveBrandingSettingsAction;
use App\DTOs\Admin\Settings\BrandingSettingsDTO;
use App\Services\Admin\Settings\BrandingService;
use App\Services\Admin\Settings\BrandingUploadService;
use App\Settings\BrandingSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Aba "Marca e tema": logotipos, favicon, nome do sistema e paleta de cores.
 *
 * Ao salvar, recarrega a página (na própria aba) para que o novo tema e os
 * logos sejam reaplicados no <head> e no chrome (topbar/sidebar).
 */
class AbaBranding extends Component
{
    use WithFileUploads;

    public string $nome_sistema = '';

    public string $slogan = '';

    public string $cor_primaria = '';

    public string $cor_secundaria = '';

    public string $cor_sucesso = '';

    public string $cor_warning = '';

    public string $cor_perigo = '';

    public string $cor_info = '';

    /** @var \Illuminate\Http\UploadedFile|null */
    public $logo = null;

    /** @var \Illuminate\Http\UploadedFile|null */
    public $logo_dark = null;

    /** @var \Illuminate\Http\UploadedFile|null */
    public $logo_sm = null;

    /** @var \Illuminate\Http\UploadedFile|null */
    public $favicon = null;

    public function mount(BrandingSettings $settings): void
    {
        $this->nome_sistema = $settings->nome_sistema;
        $this->slogan = $settings->slogan;
        $this->cor_primaria = $settings->cor_primaria;
        $this->cor_secundaria = $settings->cor_secundaria;
        $this->cor_sucesso = $settings->cor_sucesso;
        $this->cor_warning = $settings->cor_warning;
        $this->cor_perigo = $settings->cor_perigo;
        $this->cor_info = $settings->cor_info;
    }

    public function salvar(SaveBrandingSettingsAction $action, BrandingUploadService $uploads): void
    {
        $this->validate();

        $action->execute(new BrandingSettingsDTO(
            nome_sistema: $this->nome_sistema,
            slogan: $this->slogan,
            cor_primaria: $this->cor_primaria,
            cor_secundaria: $this->cor_secundaria,
            cor_sucesso: $this->cor_sucesso,
            cor_warning: $this->cor_warning,
            cor_perigo: $this->cor_perigo,
            cor_info: $this->cor_info,
            logo_arquivo: $this->logo !== null ? $uploads->armazenarLogo($this->logo, 'light') : null,
            logo_dark_arquivo: $this->logo_dark !== null ? $uploads->armazenarLogo($this->logo_dark, 'dark') : null,
            logo_sm_arquivo: $this->logo_sm !== null ? $uploads->armazenarLogo($this->logo_sm, 'sm') : null,
            favicon_arquivo: $this->favicon !== null ? $uploads->armazenarFavicon($this->favicon) : null,
        ));

        session()->flash('success', 'Marca e tema salvos.');

        // Recarrega na própria aba para reaplicar cores/logo/favicon no chrome.
        $this->redirect(route('admin.configuracoes.index', ['aba' => 'branding']));
    }

    public function render(BrandingService $branding): View
    {
        return view('livewire.admin.configuracao.aba-branding', [
            'logoAtual' => $branding->logoUrl('light'),
            'logoDarkAtual' => $branding->logoUrl('dark'),
            'logoSmAtual' => $branding->logoUrl('sm'),
            'faviconAtual' => $branding->faviconUrl(),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $hex = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'nome_sistema' => ['required', 'string', 'max:120'],
            'slogan' => ['nullable', 'string', 'max:160'],
            'cor_primaria' => $hex,
            'cor_secundaria' => $hex,
            'cor_sucesso' => $hex,
            'cor_warning' => $hex,
            'cor_perigo' => $hex,
            'cor_info' => $hex,
            'logo' => ['nullable', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'max:2048'],
            'logo_sm' => ['nullable', 'image', 'max:1024'],
            'favicon' => ['nullable', 'image', 'max:512'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nome_sistema' => 'nome do sistema',
            'cor_primaria' => 'cor primária',
            'cor_secundaria' => 'cor secundária',
            'cor_sucesso' => 'cor de sucesso',
            'cor_warning' => 'cor de aviso',
            'cor_perigo' => 'cor de perigo',
            'cor_info' => 'cor de informação',
            'logo_dark' => 'logo (tema escuro)',
            'logo_sm' => 'logo reduzido',
        ];
    }
}
