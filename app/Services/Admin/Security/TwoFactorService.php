<?php

declare(strict_types=1);

namespace App\Services\Admin\Security;

use App\Models\AdminUser;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
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
    private const RECOVERY_CODES = 8;

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

    public function verificarCodigo(string $secret, string $codigo): bool
    {
        // Janela de ±1 passo (30s) para tolerar pequena defasagem de relógio.
        return $this->google2fa->verifyKey($secret, $codigo, 1);
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
}
