<?php

declare(strict_types=1);

namespace App\Services\Admin\Settings;

use App\Settings\BrandingSettings;
use Illuminate\Support\Facades\Storage;

/**
 * Resolve a identidade visual configurada para uso nas views.
 *
 * Logos/favicon caem no fallback estático de config/branding.php quando o
 * cliente ainda não enviou os seus. As cores são emitidas como CSS custom
 * properties que sobrescrevem o @theme do Tailwind em runtime (sem rebuild).
 */
final class BrandingService
{
    public function __construct(private readonly BrandingSettings $settings) {}

    /**
     * @param  'light'|'dark'|'sm'  $variante
     */
    public function logoUrl(string $variante = 'light'): string
    {
        $arquivo = match ($variante) {
            'dark' => $this->settings->logo_dark_arquivo,
            'sm' => $this->settings->logo_sm_arquivo,
            default => $this->settings->logo_arquivo,
        };

        if ($arquivo !== '') {
            return Storage::disk('public')->url($arquivo);
        }

        $fallback = match ($variante) {
            'dark' => config('branding.logo_dark_path'),
            'sm' => config('branding.logo_sm_path'),
            default => config('branding.logo_path'),
        };

        return asset((string) $fallback);
    }

    public function faviconUrl(): string
    {
        return $this->settings->favicon_arquivo !== ''
            ? Storage::disk('public')->url($this->settings->favicon_arquivo)
            : asset('images/favicon.ico');
    }

    public function nomeSistema(): string
    {
        return $this->settings->nome_sistema !== ''
            ? $this->settings->nome_sistema
            : (string) config('app.name');
    }

    public function slogan(): string
    {
        return $this->settings->slogan;
    }

    /**
     * CSS custom properties da paleta, para injeção em <style> no <head>.
     * Só emite cores hex válidas (#RRGGBB) — evita injeção de CSS.
     */
    public function cssVariables(): string
    {
        $cores = [
            'primary' => $this->settings->cor_primaria,
            'secondary' => $this->settings->cor_secundaria,
            'success' => $this->settings->cor_sucesso,
            'warning' => $this->settings->cor_warning,
            'danger' => $this->settings->cor_perigo,
            'info' => $this->settings->cor_info,
        ];

        $linhas = [];

        foreach ($cores as $nome => $hex) {
            if (! $this->hexValido($hex)) {
                continue;
            }

            $linhas[] = "--color-{$nome}: {$hex};";
            $linhas[] = "--color-{$nome}-hover: color-mix(in srgb, {$hex} 88%, #000);";
        }

        // PowerGrid (focus ring / checkbox) usa a escala primary-500/600.
        if ($this->hexValido($this->settings->cor_primaria)) {
            $linhas[] = "--color-primary-500: {$this->settings->cor_primaria};";
            $linhas[] = "--color-primary-600: color-mix(in srgb, {$this->settings->cor_primaria} 85%, #000);";
        }

        return implode("\n", $linhas);
    }

    private function hexValido(string $valor): bool
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $valor) === 1;
    }
}
