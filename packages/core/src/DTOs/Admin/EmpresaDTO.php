<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin;

final readonly class EmpresaDTO
{
    public function __construct(
        public string $nome,
        public ?string $razaoSocial = null,
        public ?string $cnpj = null,
        public ?string $inscricaoEstadual = null,
        public ?string $telefone = null,
        public ?string $email = null,
        public ?string $siteUrl = null,
        public ?string $corPrimaria = null,
        public ?string $corSecundaria = null,
        public ?string $corSucesso = null,
        public ?string $corWarning = null,
        public ?string $corPerigo = null,
        public ?string $corInfo = null,
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
            razaoSocial: $texto('razao_social'),
            cnpj: $texto('cnpj'),
            inscricaoEstadual: $texto('inscricao_estadual'),
            telefone: $texto('telefone'),
            email: $texto('email'),
            siteUrl: $texto('site_url'),
            corPrimaria: $texto('cor_primaria'),
            corSecundaria: $texto('cor_secundaria'),
            corSucesso: $texto('cor_sucesso'),
            corWarning: $texto('cor_warning'),
            corPerigo: $texto('cor_perigo'),
            corInfo: $texto('cor_info'),
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
            'razao_social' => $this->razaoSocial,
            'cnpj' => $this->cnpj,
            'inscricao_estadual' => $this->inscricaoEstadual,
            'telefone' => $this->telefone,
            'email' => $this->email,
            'site_url' => $this->siteUrl,
            'cor_primaria' => $this->corPrimaria,
            'cor_secundaria' => $this->corSecundaria,
            'cor_sucesso' => $this->corSucesso,
            'cor_warning' => $this->corWarning,
            'cor_perigo' => $this->corPerigo,
            'cor_info' => $this->corInfo,
            'ativo' => $this->ativo,
        ];
    }
}
