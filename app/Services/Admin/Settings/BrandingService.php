<?php

declare(strict_types=1);

namespace App\Services\Admin\Settings;

use App\Settings\BrandingSettings;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
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
     * Título do documento: "{página} — {empresa ativa}" com fallback para o
     * nome do sistema quando não há empresa no contexto (login, setup).
     */
    public function tituloPagina(?string $titulo = null): string
    {
        $contexto = $this->empresa()?->nome ?: $this->nomeSistema();

        return filled($titulo) ? "{$titulo} — {$contexto}" : $contexto;
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
            // Cor de texto legível sobre a cor (maior contraste entre claro/escuro).
            $linhas[] = "--color-{$nome}-foreground: {$this->corDeContraste($hex)};";
        }

        // Escala tonal da primária (50–950): motor "1 cor → paleta". Inclui o
        // 500/600 que o PowerGrid (focus ring / checkbox) consome.
        if ($this->hexValido($cores['primary'])) {
            foreach ($this->escalaTonal($cores['primary']) as $tom => $valor) {
                $linhas[] = "--color-primary-{$tom}: {$valor};";
            }
        }

        return implode("\n", $linhas);
    }

    /**
     * Escala tonal 50–950 derivada de 1 cor, via color-mix (avaliado no browser).
     * 50–400 clareiam (mix com branco); 600–950 escurecem (mix com preto).
     *
     * @return array<int, string>
     */
    private function escalaTonal(string $hex): array
    {
        return [
            50 => "color-mix(in srgb, {$hex} 8%, #fff)",
            100 => "color-mix(in srgb, {$hex} 16%, #fff)",
            200 => "color-mix(in srgb, {$hex} 30%, #fff)",
            300 => "color-mix(in srgb, {$hex} 50%, #fff)",
            400 => "color-mix(in srgb, {$hex} 72%, #fff)",
            500 => $hex,
            600 => "color-mix(in srgb, {$hex} 85%, #000)",
            700 => "color-mix(in srgb, {$hex} 70%, #000)",
            800 => "color-mix(in srgb, {$hex} 55%, #000)",
            900 => "color-mix(in srgb, {$hex} 42%, #000)",
            950 => "color-mix(in srgb, {$hex} 30%, #000)",
        ];
    }

    /**
     * Texto legível sobre a cor: escolhe branco ou escuro pelo maior contraste
     * WCAG (color-contrast() do CSS ainda tem suporte irregular).
     */
    private function corDeContraste(string $hex): string
    {
        $lumCor = $this->luminancia($hex);
        $contrasteBranco = (1.0 + 0.05) / ($lumCor + 0.05);
        $contrasteEscuro = ($lumCor + 0.05) / ($this->luminancia('#1c2531') + 0.05);

        return $contrasteBranco >= $contrasteEscuro ? '#ffffff' : '#1c2531';
    }

    /** Luminância relativa WCAG (0 = preto, 1 = branco). */
    private function luminancia(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $canal = static function (int $valor): float {
            $c = $valor / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $canal((int) hexdec(substr($hex, 0, 2)))
            + 0.7152 * $canal((int) hexdec(substr($hex, 2, 2)))
            + 0.0722 * $canal((int) hexdec(substr($hex, 4, 2)));
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
