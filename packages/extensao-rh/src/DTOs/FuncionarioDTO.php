<?php

declare(strict_types=1);

namespace HT2ML\Rh\DTOs;

use HT2ML\Rh\Enums\StatusFuncionario;

final readonly class FuncionarioDTO
{
    public function __construct(
        public string $nome = '',
        public string $cpf = '',
        public string $cargo = '',
        public int $salario = 0,
        public string $admissao = '',
        public StatusFuncionario $status = StatusFuncionario::Ativo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nome: (string) ($data['nome'] ?? ''),
            cpf: (string) ($data['cpf'] ?? ''),
            cargo: (string) ($data['cargo'] ?? ''),
            salario: (int) ($data['salario'] ?? 0),
            admissao: (string) ($data['admissao'] ?? ''),
            status: StatusFuncionario::from((string) ($data['status'] ?? 'ativo')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'nome' => $this->nome,
            'cpf' => $this->cpf,
            'cargo' => $this->cargo,
            'salario' => $this->salario,
            'admissao' => $this->admissao,
            'status' => $this->status,
        ];
    }
}
