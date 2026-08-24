<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Configuracao;

use HT2ML\Core\Actions\Admin\Settings\SaveAppearanceSettingsAction;
use HT2ML\Core\Actions\Admin\Settings\SaveBrandingSettingsAction;
use HT2ML\Core\DTOs\Admin\Settings\AppearanceSettingsDTO;
use HT2ML\Core\DTOs\Admin\Settings\BrandingSettingsDTO;
use HT2ML\Core\Enums\Admin\Appearance\LayoutWidth;
use HT2ML\Core\Enums\Admin\Appearance\MenuColor;
use HT2ML\Core\Enums\Admin\Appearance\SidenavSize;
use HT2ML\Core\Enums\Admin\Appearance\Skin;
use HT2ML\Core\Enums\Admin\Appearance\TemaPadrao;
use HT2ML\Core\Enums\Admin\Appearance\ThemePreset;
use HT2ML\Core\Enums\Admin\Appearance\TopbarColor;
use HT2ML\Core\Services\Admin\Settings\BrandingService;
use HT2ML\Core\Services\Admin\Settings\BrandingUploadService;
use HT2ML\Core\Services\Admin\Settings\ThemePresetCatalog;
use HT2ML\Core\Settings\AppearanceSettings;
use HT2ML\Core\Settings\BrandingSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Centro de Aparência: identidade (logos, nome), paleta de cores, presets e
 * layout/tema do Inspinia, com preview ao vivo. Salva BrandingSettings e
 * AppearanceSettings juntos, sem recarregar (o preview já reflete o resultado).
 */
class AbaAparencia extends Component
{
    use WithFileUploads;

    // Identidade
    public string $nome_sistema = '';

    public string $slogan = '';

    // Cores
    public string $cor_primaria = '';

    public string $cor_secundaria = '';

    public string $cor_sucesso = '';

    public string $cor_warning = '';

    public string $cor_perigo = '';

    public string $cor_info = '';

    // Uploads (null = manter atual)
    /** @var \Illuminate\Http\UploadedFile|null */
    public $logo = null;

    /** @var \Illuminate\Http\UploadedFile|null */
    public $logo_dark = null;

    /** @var \Illuminate\Http\UploadedFile|null */
    public $logo_sm = null;

    /** @var \Illuminate\Http\UploadedFile|null */
    public $favicon = null;

    // Layout / tema
    public string $tema_padrao = 'light';

    public string $skin = 'default';

    public string $topbar_color = 'light';

    public string $menu_color = 'dark';

    public string $layout_width = 'fluid';

    public string $sidenav_size_padrao = 'default';

    public bool $sidenav_user = true;

    public bool $permitir_preferencia_usuario = true;

    public function mount(BrandingSettings $branding, AppearanceSettings $appearance): void
    {
        $this->nome_sistema = $branding->nome_sistema;
        $this->slogan = $branding->slogan;
        $this->cor_primaria = $branding->cor_primaria;
        $this->cor_secundaria = $branding->cor_secundaria;
        $this->cor_sucesso = $branding->cor_sucesso;
        $this->cor_warning = $branding->cor_warning;
        $this->cor_perigo = $branding->cor_perigo;
        $this->cor_info = $branding->cor_info;

        $this->tema_padrao = $appearance->tema_padrao;
        $this->skin = $appearance->skin;
        $this->topbar_color = $appearance->topbar_color;
        $this->menu_color = $appearance->menu_color;
        $this->layout_width = $appearance->layout_width;
        $this->sidenav_size_padrao = $appearance->sidenav_size_padrao;
        $this->sidenav_user = $appearance->sidenav_user;
        $this->permitir_preferencia_usuario = $appearance->permitir_preferencia_usuario;
    }

    /** Aplica um preset curado às cores e ao chrome (sem salvar). */
    public function aplicarPreset(string $preset, ThemePresetCatalog $catalog): void
    {
        $enum = ThemePreset::tryFrom($preset);

        if ($enum === null) {
            return;
        }

        $dto = $catalog->paraEnum($enum);

        $this->cor_primaria = $dto->cor_primaria;
        $this->cor_secundaria = $dto->cor_secundaria;
        $this->cor_sucesso = $dto->cor_sucesso;
        $this->cor_warning = $dto->cor_warning;
        $this->cor_perigo = $dto->cor_perigo;
        $this->cor_info = $dto->cor_info;
        $this->skin = $dto->skin;
        $this->topbar_color = $dto->topbar_color;
        $this->menu_color = $dto->menu_color;
        $this->tema_padrao = $dto->tema_padrao;
    }

    public function salvar(
        SaveBrandingSettingsAction $salvarBranding,
        SaveAppearanceSettingsAction $salvarAppearance,
        BrandingUploadService $uploads,
    ): void {
        $this->validate();

        $salvarBranding->execute(new BrandingSettingsDTO(
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

        $salvarAppearance->execute(new AppearanceSettingsDTO(
            tema_padrao: $this->tema_padrao,
            skin: $this->skin,
            topbar_color: $this->topbar_color,
            menu_color: $this->menu_color,
            layout_width: $this->layout_width,
            sidenav_size_padrao: $this->sidenav_size_padrao,
            sidenav_user: $this->sidenav_user,
            permitir_preferencia_usuario: $this->permitir_preferencia_usuario,
        ));

        $this->reset(['logo', 'logo_dark', 'logo_sm', 'favicon']);

        session()->flash('success', 'Aparência salva.');

        // O preview já reflete o estado salvo: não recarrega. O listener limpa o
        // sessionStorage para que a próxima navegação use os novos defaults.
        $this->dispatch('appearance-applied');
    }

    public function descartar(BrandingSettings $branding, AppearanceSettings $appearance): void
    {
        $this->reset(['logo', 'logo_dark', 'logo_sm', 'favicon']);
        $this->mount($branding, $appearance);
        $this->dispatch('appearance-discarded');
    }

    public function render(BrandingService $branding): View
    {
        return view('livewire.admin.configuracao.aba-aparencia', [
            'logoAtual' => $branding->logoUrl('light'),
            'logoDarkAtual' => $branding->logoUrl('dark'),
            'logoSmAtual' => $branding->logoUrl('sm'),
            'faviconAtual' => $branding->faviconUrl(),
            'presets' => ThemePreset::cases(),
            'skins' => Skin::cases(),
            'temas' => TemaPadrao::cases(),
            'topbarColors' => TopbarColor::cases(),
            'menuColors' => MenuColor::cases(),
            'layoutWidths' => LayoutWidth::options(),
            'sidenavSizes' => SidenavSize::options(),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
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
            'tema_padrao' => ['required', Rule::in(TemaPadrao::valores())],
            'skin' => ['required', Rule::in(Skin::valores())],
            'topbar_color' => ['required', Rule::in(TopbarColor::valores())],
            'menu_color' => ['required', Rule::in(MenuColor::valores())],
            'layout_width' => ['required', Rule::in(LayoutWidth::valores())],
            'sidenav_size_padrao' => ['required', Rule::in(SidenavSize::valores())],
            'sidenav_user' => ['boolean'],
            'permitir_preferencia_usuario' => ['boolean'],
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
            'tema_padrao' => 'tema padrão',
        ];
    }
}
