<?php

declare(strict_types=1);

namespace App\Services\Admin\Settings;

use App\Models\Empresa;
use App\Settings\BrandingSettings;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Storage;

/**
 * Resolve a identidade visual para uso nas views.
 *
 * Precedência: empresa ativa (logo/favicon/cores) → settings da instância →
 * fallback estático de config/branding.php. Assim o painel muda conforme a
 * empresa ativa, enquanto login/setup (sem empresa) usam o branding da instância.
 * As cores são emitidas como CSS custom properties que sobrescrevem o @theme do
 * Tailwind em runtime (sem rebuild). Nome do sistema e slogan seguem da instância.
 */
final class BrandingService
{
    private bool $empresaCarregada = false;

    private ?Empresa $empresaAtiva = null;

    public function __construct(private readonly BrandingSettings $settings) {}

    /**
     * @param  'light'|'dark'|'sm'  $variante
     */
    public function logoUrl(string $variante = 'light'): string
    {
        // Empresa ativa tem prioridade para light/dark; 'sm' é só da instância.
        if ($variante !== 'sm') {
            $empresaArquivo = $variante === 'dark'
                ? $this->empresa()?->logo_dark_arquivo
                : $this->empresa()?->logo_arquivo;

            if (is_string($empresaArquivo) && $empresaArquivo !== '') {
                return Storage::disk('public')->url($empresaArquivo);
            }
        }

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
        $empresaFavicon = $this->empresa()?->favicon_arquivo;

        if (is_string($empresaFavicon) && $empresaFavicon !== '') {
            return Storage::disk('public')->url($empresaFavicon);
        }

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
     * Cada cor vem da empresa ativa (se hex válido) ou da instância.
     * Só emite hex válido (#RRGGBB) — evita injeção de CSS.
     */
    public function cssVariables(): string
    {
        $empresa = $this->empresa();

        $cores = [
            'primary' => $this->corResolvida($empresa?->cor_primaria, $this->settings->cor_primaria),
            'secondary' => $this->corResolvida($empresa?->cor_secundaria, $this->settings->cor_secundaria),
            'success' => $this->corResolvida($empresa?->cor_sucesso, $this->settings->cor_sucesso),
            'warning' => $this->corResolvida($empresa?->cor_warning, $this->settings->cor_warning),
            'danger' => $this->corResolvida($empresa?->cor_perigo, $this->settings->cor_perigo),
            'info' => $this->corResolvida($empresa?->cor_info, $this->settings->cor_info),
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
        if ($this->hexValido($cores['primary'])) {
            $linhas[] = "--color-primary-500: {$cores['primary']};";
            $linhas[] = "--color-primary-600: color-mix(in srgb, {$cores['primary']} 85%, #000);";
        }

        return implode("\n", $linhas);
    }

    private function corResolvida(?string $empresaCor, string $settingsCor): string
    {
        return is_string($empresaCor) && $this->hexValido($empresaCor) ? $empresaCor : $settingsCor;
    }

    /**
     * Empresa ativa resolvida do contexto (memoizada por instância do serviço).
     */
    private function empresa(): ?Empresa
    {
        if (! $this->empresaCarregada) {
            $id = app(TenantContext::class)->empresaAtivaId();
            $this->empresaAtiva = $id !== null ? Empresa::query()->find($id) : null;
            $this->empresaCarregada = true;
        }

        return $this->empresaAtiva;
    }

    private function hexValido(string $valor): bool
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $valor) === 1;
    }
}
