<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\DTOs;

use HT2ML\FiscalBr\Enums\TipoCfop;

final readonly class CfopDTO
{
    public function __construct(
        public string $codigo = '',
        public string $descricao = '',
        public ?TipoCfop $tipo = null,
        public ?string $aplicacao = null,
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
            tipo: isset($data['tipo']) && $data['tipo'] !== ''
                ? TipoCfop::from((string) $data['tipo'])
                : null,
            aplicacao: $texto('aplicacao'),
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
            'tipo' => $this->tipo,
            'aplicacao' => $this->aplicacao,
        ];
    }
}
