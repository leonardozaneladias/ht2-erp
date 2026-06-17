<?php

declare(strict_types=1);

namespace HT2ERP\Rh\DTOs;

use HT2ERP\Rh\Enums\StatusDepartamento;

final readonly class DepartamentoDTO
{
    public function __construct(
        public string $nome = '',
        public string $sigla = '',
        public StatusDepartamento $status = StatusDepartamento::Ativo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nome: (string) ($data['nome'] ?? ''),
            sigla: (string) ($data['sigla'] ?? ''),
            status: StatusDepartamento::from((string) ($data['status'] ?? 'ativo')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'nome' => $this->nome,
            'sigla' => $this->sigla,
            'status' => $this->status,
        ];
    }
}
