<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Referencia;

use App\Enums\Referencia\RegiaoBrasil;

final readonly class EstadoDTO
{
    public function __construct(
        public string $codigoIbge = '',
        public string $sigla = '',
        public string $nome = '',
        public ?RegiaoBrasil $regiao = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            codigoIbge: (string) ($data['codigo_ibge'] ?? ''),
            sigla: mb_strtoupper((string) ($data['sigla'] ?? '')),
            nome: (string) ($data['nome'] ?? ''),
            regiao: isset($data['regiao']) && $data['regiao'] !== ''
                ? RegiaoBrasil::from((string) $data['regiao'])
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
            'sigla' => $this->sigla,
            'nome' => $this->nome,
            'regiao' => $this->regiao,
        ];
    }
}
