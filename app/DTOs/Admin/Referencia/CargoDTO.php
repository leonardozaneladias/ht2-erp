<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Referencia;

final readonly class CargoDTO
{
    public function __construct(
        public string $codigoCbo = '',
        public string $descricao = '',
        public bool $ativo = true,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            codigoCbo: (string) ($data['codigo_cbo'] ?? ''),
            descricao: (string) ($data['descricao'] ?? ''),
            ativo: (bool) ($data['ativo'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'codigo_cbo' => $this->codigoCbo,
            'descricao' => $this->descricao,
            'ativo' => $this->ativo,
        ];
    }
}
