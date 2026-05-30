<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

use App\Enums\TipoConcessao;

final readonly class ConcessaoAcessoDTO
{
    public function __construct(
        public int $adminUserId,
        public string $permissao,
        public TipoConcessao $tipo,
        public string $motivo,
        public ?string $expiraEm = null,
        public ?string $contextoTipo = null,
        public ?int $contextoId = null,
    ) {}

    /**
     * @param  array{adminUserId: int, permissao: string, tipo: TipoConcessao|string, motivo: string, expiraEm?: ?string, contextoTipo?: ?string, contextoId?: ?int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            adminUserId: $data['adminUserId'],
            permissao: $data['permissao'],
            tipo: $data['tipo'] instanceof TipoConcessao ? $data['tipo'] : TipoConcessao::from($data['tipo']),
            motivo: $data['motivo'],
            expiraEm: $data['expiraEm'] ?? null,
            contextoTipo: $data['contextoTipo'] ?? null,
            contextoId: $data['contextoId'] ?? null,
        );
    }
}
