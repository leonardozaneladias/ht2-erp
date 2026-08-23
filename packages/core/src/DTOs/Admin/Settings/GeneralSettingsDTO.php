<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin\Settings;

final readonly class GeneralSettingsDTO
{
    public function __construct(
        public string $nome_cliente,
        public string $razao_social,
        public string $cnpj,
        public string $inscricao_estadual,
        public string $telefone,
        public string $email_contato,
        public string $endereco,
        public string $numero,
        public string $bairro,
        public string $cidade,
        public string $estado,
        public string $cep,
        public string $site_url,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nome_cliente: (string) ($data['nome_cliente'] ?? ''),
            razao_social: (string) ($data['razao_social'] ?? ''),
            cnpj: (string) ($data['cnpj'] ?? ''),
            inscricao_estadual: (string) ($data['inscricao_estadual'] ?? ''),
            telefone: (string) ($data['telefone'] ?? ''),
            email_contato: (string) ($data['email_contato'] ?? ''),
            endereco: (string) ($data['endereco'] ?? ''),
            numero: (string) ($data['numero'] ?? ''),
            bairro: (string) ($data['bairro'] ?? ''),
            cidade: (string) ($data['cidade'] ?? ''),
            estado: (string) ($data['estado'] ?? ''),
            cep: (string) ($data['cep'] ?? ''),
            site_url: (string) ($data['site_url'] ?? ''),
        );
    }
}
