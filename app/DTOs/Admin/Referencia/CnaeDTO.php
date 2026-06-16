<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Referencia;

final readonly class CnaeDTO
{
    public function __construct(
        public string $codigo = '',
        public string $descricao = '',
        public ?string $secao = null,
        public ?string $divisao = null,
        public ?string $grupo = null,
        public ?string $classe = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $texto = static fn (string $chave): ?string => isset($data[$chave]) && $data[$chave] !== ''
            ? (string) $data[$chave]
            : null;

        return new self(
            codigo: (string) ($data['codigo'] ?? ''),
            descricao: (string) ($data['descricao'] ?? ''),
            secao: $texto('secao'),
            divisao: $texto('divisao'),
            grupo: $texto('grupo'),
            classe: $texto('classe'),
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
            'secao' => $this->secao,
            'divisao' => $this->divisao,
            'grupo' => $this->grupo,
            'classe' => $this->classe,
        ];
    }
}
