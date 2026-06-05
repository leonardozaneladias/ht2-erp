<?php

declare(strict_types=1);

namespace App\Support\Impersonation;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * Estado da personificação (act-as) na sessão. Espelha o TenantContext: é a fonte
 * única do "estou personificando" consumida pelo banner, middleware de expiração,
 * travas de ações sensíveis e atribuição de auditoria.
 */
final class ImpersonationContext
{
    private const ORIGINAL = 'impersonate.original_id';

    private const STARTED = 'impersonate.started_at';

    private const MOTIVO = 'impersonate.motivo';

    public function iniciar(int $originalId, string $motivo): void
    {
        session([
            self::ORIGINAL => $originalId,
            self::STARTED => time(),
            self::MOTIVO => $motivo,
        ]);
    }

    public function ativo(): bool
    {
        return is_int(session(self::ORIGINAL));
    }

    public function originalId(): ?int
    {
        $id = session(self::ORIGINAL);

        return is_int($id) ? $id : null;
    }

    public function motivo(): ?string
    {
        $motivo = session(self::MOTIVO);

        return is_string($motivo) ? $motivo : null;
    }

    /**
     * Momento de início como timestamp UNIX (segundos), ou null se inativo.
     */
    public function iniciadoEm(): ?int
    {
        $ts = session(self::STARTED);

        return is_int($ts) ? $ts : null;
    }

    public function expirado(int $minutos): bool
    {
        $iniciadoEm = $this->iniciadoEm();

        if ($iniciadoEm === null) {
            return false;
        }

        return (time() - $iniciadoEm) >= $minutos * 60;
    }

    public function encerrar(): void
    {
        session()->forget([self::ORIGINAL, self::STARTED, self::MOTIVO]);
    }

    /**
     * Barreira para ações sensíveis: recusa quando há personificação ativa.
     */
    public function garantirNaoPersonificando(): void
    {
        if ($this->ativo()) {
            throw new AuthorizationException('Ação indisponível durante a personificação.');
        }
    }
}
