<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

use App\Enums\PublicoComunicado;
use App\Enums\TipoNotificacao;

final readonly class ComunicadoDTO
{
    public function __construct(
        public TipoNotificacao $tipo,
        public string $titulo,
        public string $mensagem,
        public PublicoComunicado $publico,
        public ?string $papel = null,
    ) {}

    /**
     * @param  array{tipo: string, titulo: string, mensagem: string, publico: string, papel?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tipo: TipoNotificacao::from($data['tipo']),
            titulo: $data['titulo'],
            mensagem: $data['mensagem'],
            publico: PublicoComunicado::from($data['publico']),
            papel: $data['papel'] ?? null,
        );
    }
}
