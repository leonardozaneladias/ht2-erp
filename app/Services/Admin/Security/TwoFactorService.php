<?php

declare(strict_types=1);

namespace App\Services\Admin\Security;

use App\Models\AdminUser;
use App\Notifications\CodigoVerificacaoEmailNotification;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Operações de autenticação em dois fatores (TOTP) para o guard admin.
 *
 * O segredo é guardado criptografado (cast encrypted no model). Os códigos de
 * recuperação são guardados HASHEADOS (one-way, single-use) e exibidos em texto
 * puro uma única vez na geração — escolha consciente por mais segurança.
 */
final class TwoFactorService
{
    /** Validade do código enviado por e-mail, em segundos (10 min). */
    public const EMAIL_CODE_TTL = 600;

    /** Intervalo mínimo entre envios de código por e-mail, em segundos. */
    public const EMAIL_RESEND_COOLDOWN = 60;

    private const RECOVERY_CODES = 8;

    private const EMAIL_CODE_CACHE_PREFIX = '2fa-email-code:';

    private const EMAIL_SEND_COOLDOWN_PREFIX = 'two-factor-email-send:';

    public function __construct(private readonly Google2FA $google2fa) {}

    public function gerarSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeSvg(AdminUser $usuario, string $secret): string
    {
        $uri = $this->google2fa->getQRCodeUrl(
            (string) config('app.name'),
            $usuario->email,
            $secret,
        );

        $renderer = new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($uri);
    }

    /**
     * Verifica um código TOTP e devolve o contador de janela aceito (para
     * persistir e impedir replay) ou false. Informe o último timestamp aceito
     * para rejeitar a reutilização do mesmo código dentro da janela de validade.
     */
    public function verificarCodigo(string $secret, string $codigo, ?int $ultimoTimestamp = null): int|false
    {
        // Janela de ±1 passo (30s) para tolerar pequena defasagem de relógio.
        // oldTimestamp nunca nulo (?? 0): garante o retorno do timestamp inteiro
        // — com null, o verifyKeyNewer devolveria apenas true.
        $resultado = $this->google2fa->verifyKeyNewer($secret, $codigo, $ultimoTimestamp ?? 0, 1);

        return is_int($resultado) ? $resultado : false;
    }

    /**
     * Códigos de recuperação em texto puro (exibidos uma única vez).
     *
     * @return list<string>
     */
    public function gerarRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODES))
            ->map(fn (): string => Str::upper(Str::random(5)) . '-' . Str::upper(Str::random(5)))
            ->all();
    }

    /**
     * @param  list<string>  $codigos
     * @return list<string>
     */
    public function hashearRecoveryCodes(array $codigos): array
    {
        return array_map(static fn (string $codigo): string => Hash::make($codigo), $codigos);
    }

    /**
     * Consome um código de recuperação: devolve os hashes restantes (sem o
     * usado) ou null se o código não confere.
     *
     * @param  list<string>  $hashes
     * @return list<string>|null
     */
    public function consumirRecoveryCode(array $hashes, string $codigo): ?array
    {
        foreach ($hashes as $indice => $hash) {
            if (Hash::check($codigo, $hash)) {
                unset($hashes[$indice]);

                return array_values($hashes);
            }
        }

        return null;
    }

    /**
     * Gera um código numérico de 6 dígitos, guarda o hash em cache (single-use,
     * com TTL) e o envia por e-mail. O código em claro nunca é persistido — só
     * trafega no e-mail; no servidor fica apenas o hash.
     *
     * Respeita o cooldown de reenvio: devolve false (sem enviar) quando ainda
     * está dentro do intervalo mínimo entre envios.
     */
    public function dispararCodigoEmail(AdminUser $usuario): bool
    {
        $chaveCooldown = self::EMAIL_SEND_COOLDOWN_PREFIX . $usuario->id;

        if (RateLimiter::tooManyAttempts($chaveCooldown, 1)) {
            return false;
        }

        RateLimiter::hit($chaveCooldown, self::EMAIL_RESEND_COOLDOWN);

        $codigo = (string) random_int(100000, 999999);

        Cache::put(
            self::EMAIL_CODE_CACHE_PREFIX . $usuario->id,
            Hash::make($codigo),
            self::EMAIL_CODE_TTL,
        );

        $usuario->notify(new CodigoVerificacaoEmailNotification($codigo, intdiv(self::EMAIL_CODE_TTL, 60)));

        return true;
    }

    /**
     * Verifica o código de e-mail informado. Single-use: em caso de sucesso o
     * código é descartado do cache para impedir reuso (anti-replay).
     */
    public function verificarCodigoEmail(AdminUser $usuario, string $codigo): bool
    {
        $chave = self::EMAIL_CODE_CACHE_PREFIX . $usuario->id;
        $hash = Cache::get($chave);

        if (! is_string($hash) || ! Hash::check($codigo, $hash)) {
            return false;
        }

        Cache::forget($chave);

        return true;
    }
}
