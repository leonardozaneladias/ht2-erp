<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Referencia;

final readonly class BancoDTO
{
    public function __construct(
        public string $ispb = '',
        public ?string $codigoCompe = null,
        public string $nome = '',
        public ?string $nomeCompleto = null,
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
            ispb: (string) ($data['ispb'] ?? ''),
            codigoCompe: $texto('codigo_compe'),
            nome: (string) ($data['nome'] ?? ''),
            nomeCompleto: $texto('nome_completo'),
            ativo: (bool) ($data['ativo'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'ispb' => $this->ispb,
            'codigo_compe' => $this->codigoCompe,
            'nome' => $this->nome,
            'nome_completo' => $this->nomeCompleto,
            'ativo' => $this->ativo,
        ];
    }
}
