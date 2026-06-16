<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Referencia;

final readonly class PaisDTO
{
    public function __construct(
        public string $codigoIso2 = '',
        public ?string $codigoIso3 = null,
        public ?string $codigoNumerico = null,
        public string $nome = '',
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
            codigoIso2: mb_strtoupper((string) ($data['codigo_iso2'] ?? '')),
            codigoIso3: $texto('codigo_iso3'),
            codigoNumerico: $texto('codigo_numerico'),
            nome: (string) ($data['nome'] ?? ''),
            ativo: (bool) ($data['ativo'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'codigo_iso2' => $this->codigoIso2,
            'codigo_iso3' => $this->codigoIso3,
            'codigo_numerico' => $this->codigoNumerico,
            'nome' => $this->nome,
            'ativo' => $this->ativo,
        ];
    }
}
