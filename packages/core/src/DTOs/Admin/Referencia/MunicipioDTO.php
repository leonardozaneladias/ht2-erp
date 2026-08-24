<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin\Referencia;

final readonly class MunicipioDTO
{
    public function __construct(
        public string $codigoIbge = '',
        public string $nome = '',
        public ?int $estadoId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            codigoIbge: (string) ($data['codigo_ibge'] ?? ''),
            nome: (string) ($data['nome'] ?? ''),
            estadoId: isset($data['estado_id']) && $data['estado_id'] !== ''
                ? (int) $data['estado_id']
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'codigo_ibge' => $this->codigoIbge,
            'nome' => $this->nome,
            'estado_id' => $this->estadoId,
        ];
    }
}
