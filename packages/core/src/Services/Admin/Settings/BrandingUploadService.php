<?php

declare(strict_types=1);

namespace HT2ML\Core\Services\Admin\Settings;

use HT2ML\Core\Settings\BrandingSettings;
use Illuminate\Http\UploadedFile;

/**
 * Resolve o arquivo anterior de cada variante de logo/favicon e delega a
 * gravação (com remoção do antigo) ao SettingsFileUploadService.
 */
final class BrandingUploadService
{
    private const DIRETORIO = 'branding';

    public function __construct(
        private readonly BrandingSettings $settings,
        private readonly SettingsFileUploadService $uploads,
    ) {}

    /**
     * @param  'light'|'dark'|'sm'  $variante
     */
    public function armazenarLogo(UploadedFile $arquivo, string $variante): string
    {
        $anterior = match ($variante) {
            'dark' => $this->settings->logo_dark_arquivo,
            'sm' => $this->settings->logo_sm_arquivo,
            default => $this->settings->logo_arquivo,
        };

        return $this->uploads->substituir($arquivo, $anterior, self::DIRETORIO);
    }

    public function armazenarFavicon(UploadedFile $arquivo): string
    {
        return $this->uploads->substituir($arquivo, $this->settings->favicon_arquivo, self::DIRETORIO);
    }
}
