<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Referencia;

final readonly class MoedaDTO
{
    public function __construct(
        public string $codigoIso = '',
        public ?string $numerico = null,
        public string $nome = '',
        public ?string $simbolo = null,
        public int $casasDecimais = 2,
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
            codigoIso: mb_strtoupper((string) ($data['codigo_iso'] ?? '')),
            numerico: $texto('numerico'),
            nome: (string) ($data['nome'] ?? ''),
            simbolo: $texto('simbolo'),
            casasDecimais: (int) ($data['casas_decimais'] ?? 2),
            ativo: (bool) ($data['ativo'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'codigo_iso' => $this->codigoIso,
            'numerico' => $this->numerico,
            'nome' => $this->nome,
            'simbolo' => $this->simbolo,
            'casas_decimais' => $this->casasDecimais,
            'ativo' => $this->ativo,
        ];
    }
}
