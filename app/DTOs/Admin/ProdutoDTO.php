<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

use App\Enums\StatusProduto;

final readonly class ProdutoDTO
{
    public function __construct(
        public string $nome = '',
        public string $sku = '',
        public int $preco = 0,
        public ?string $descricao = null,
        public StatusProduto $status = StatusProduto::Rascunho,
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
            sku: (string) ($data['sku'] ?? ''),
            preco: (int) ($data['preco'] ?? 0),
            descricao: $texto('descricao'),
            status: StatusProduto::from((string) ($data['status'] ?? 'rascunho')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'nome' => $this->nome,
            'sku' => $this->sku,
            'preco' => $this->preco,
            'descricao' => $this->descricao,
            'status' => $this->status,
        ];
    }
}
