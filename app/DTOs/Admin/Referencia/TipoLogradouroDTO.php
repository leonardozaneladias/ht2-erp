<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Referencia;

final readonly class TipoLogradouroDTO
{
    public function __construct(
        public string $nome = '',
        public ?string $codigo = null,
        public ?string $abrev = null,
        public bool $ativo = true,
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
            nome: (string) ($data['nome'] ?? ''),
            codigo: $texto('codigo'),
            abrev: $texto('abrev'),
            ativo: (bool) ($data['ativo'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'nome' => $this->nome,
            'codigo' => $this->codigo,
            'abrev' => $this->abrev,
            'ativo' => $this->ativo,
        ];
    }
}
