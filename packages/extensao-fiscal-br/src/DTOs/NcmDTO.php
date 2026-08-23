<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\DTOs;

final readonly class NcmDTO
{
    public function __construct(
        public string $codigo = '',
        public string $descricao = '',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            codigo: (string) ($data['codigo'] ?? ''),
            descricao: (string) ($data['descricao'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'codigo' => $this->codigo,
            'descricao' => $this->descricao,
        ];
    }
}
