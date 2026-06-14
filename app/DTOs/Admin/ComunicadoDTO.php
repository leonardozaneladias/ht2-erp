<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

use App\Enums\PublicoComunicado;
use App\Enums\TipoNotificacao;
use App\Support\Html\HtmlSanitizer;

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
            // Sanitiza o HTML do editor rich text antes de qualquer persistência.
            mensagem: HtmlSanitizer::clean($data['mensagem']),
            publico: PublicoComunicado::from($data['publico']),
            papel: $data['papel'] ?? null,
        );
    }
}
