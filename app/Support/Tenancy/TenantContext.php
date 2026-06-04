<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

/**
 * Contexto de tenancy lógico: empresa e filial ativas na sessão.
 *
 * É a fonte única do "tenant atual" consumida pelo global scope (trait
 * BelongsToEmpresa) e pela UI. Trocar de empresa reseta a filial ativa.
 */
final class TenantContext
{
    private const CHAVE_EMPRESA = 'tenant.empresa_id';

    private const CHAVE_FILIAL = 'tenant.filial_id';

    public function empresaAtivaId(): ?int
    {
        $id = session(self::CHAVE_EMPRESA);

        return is_int($id) ? $id : null;
    }

    public function filialAtivaId(): ?int
    {
        $id = session(self::CHAVE_FILIAL);

        return is_int($id) ? $id : null;
    }

    public function temEmpresa(): bool
    {
        return $this->empresaAtivaId() !== null;
    }

    public function definirEmpresa(?int $empresaId): void
    {
        session([self::CHAVE_EMPRESA => $empresaId]);
        // Trocar de empresa invalida a filial selecionada.
        session([self::CHAVE_FILIAL => null]);
    }

    public function definirFilial(?int $filialId): void
    {
        session([self::CHAVE_FILIAL => $filialId]);
    }

    public function limpar(): void
    {
        session()->forget([self::CHAVE_EMPRESA, self::CHAVE_FILIAL]);
    }
}
