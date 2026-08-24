<?php

declare(strict_types=1);

namespace HT2ML\ExemploDemo\DTOs;

use HT2ML\ExemploDemo\Enums\StatusExemplo;

final readonly class ExemploDTO
{
    public function __construct(
        public ?int $filialId = null,
        public string $nome = '',
        public string $slug = '',
        public ?string $site = null,
        public ?string $descricao = null,
        public string $email = '',
        public ?string $telefone = null,
        public ?string $cep = null,
        public ?string $cnpj = null,
        public ?string $cpf = null,
        public int $preco = 0,
        public string $custo = '',
        public int $quantidade = 0,
        public ?string $cor = null,
        public string $categoria = '',
        /** @var list<string> */
        public array $tags = [],
        public bool $destaque = false,
        public string $dataInicio = '',
        public ?string $publicadoEm = null,
        public StatusExemplo $status = StatusExemplo::Rascunho,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $html = static fn (string $chave): ?string => isset($data[$chave]) && trim((string) $data[$chave]) !== ''
            ? \HT2ML\Core\Support\Html\HtmlSanitizer::clean((string) $data[$chave])
            : null;

        $texto = static fn (string $chave): ?string => isset($data[$chave]) && $data[$chave] !== ''
            ? (string) $data[$chave]
            : null;

        return new self(
            filialId: isset($data['filial_id']) && $data['filial_id'] !== '' ? (int) $data['filial_id'] : null,
            nome: (string) ($data['nome'] ?? ''),
            slug: (string) ($data['slug'] ?? ''),
            site: $texto('site'),
            descricao: $html('descricao'),
            email: (string) ($data['email'] ?? ''),
            telefone: $texto('telefone'),
            cep: $texto('cep'),
            cnpj: $texto('cnpj'),
            cpf: $texto('cpf'),
            preco: (int) ($data['preco'] ?? 0),
            custo: (string) ($data['custo'] ?? ''),
            quantidade: (int) ($data['quantidade'] ?? 0),
            cor: $texto('cor'),
            categoria: (string) ($data['categoria'] ?? ''),
            tags: (array) ($data['tags'] ?? []),
            destaque: (bool) ($data['destaque'] ?? false),
            dataInicio: (string) ($data['data_inicio'] ?? ''),
            publicadoEm: $texto('publicado_em'),
            status: StatusExemplo::from((string) ($data['status'] ?? 'rascunho')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'filial_id' => $this->filialId,
            'nome' => $this->nome,
            'slug' => $this->slug,
            'site' => $this->site,
            'descricao' => $this->descricao,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'cep' => $this->cep,
            'cnpj' => $this->cnpj,
            'cpf' => $this->cpf,
            'preco' => $this->preco,
            'custo' => $this->custo,
            'quantidade' => $this->quantidade,
            'cor' => $this->cor,
            'categoria' => $this->categoria,
            'tags' => $this->tags,
            'destaque' => $this->destaque,
            'data_inicio' => $this->dataInicio,
            'publicado_em' => $this->publicadoEm,
            'status' => $this->status,
        ];
    }
}
