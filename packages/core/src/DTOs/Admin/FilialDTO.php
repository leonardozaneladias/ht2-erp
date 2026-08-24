<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin;

final readonly class FilialDTO
{
    public function __construct(
        public string $nome,
        public ?string $cnpj = null,
        public ?string $inscricaoEstadual = null,
        public ?string $telefone = null,
        public ?string $cidade = null,
        public ?string $estado = null,
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
            nome: (string) $data['nome'],
            cnpj: $texto('cnpj'),
            inscricaoEstadual: $texto('inscricao_estadual'),
            telefone: $texto('telefone'),
            cidade: $texto('cidade'),
            estado: $texto('estado'),
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
            'cnpj' => $this->cnpj,
            'inscricao_estadual' => $this->inscricaoEstadual,
            'telefone' => $this->telefone,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'ativo' => $this->ativo,
        ];
    }
}
