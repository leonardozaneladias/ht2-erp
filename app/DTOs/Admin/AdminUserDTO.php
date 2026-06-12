<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

final readonly class AdminUserDTO
{
    /**
     * @param  list<string>  $roles  Nomes de roles do guard admin
     */
    public function __construct(
        public string $nome,
        public string $email,
        public bool $ativo,
        public array $roles = [],
        public ?string $password = null,
        public ?string $telefone = null,
        public ?string $cargo = null,
    ) {}

    /**
     * @param  array{nome: string, email: string, ativo?: bool, roles?: list<string>, password?: ?string, telefone?: ?string, cargo?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        $texto = static fn (string $chave): ?string => isset($data[$chave]) && $data[$chave] !== ''
            ? (string) $data[$chave]
            : null;

        return new self(
            nome: $data['nome'],
            email: $data['email'],
            ativo: $data['ativo'] ?? true,
            roles: $data['roles'] ?? [],
            password: $data['password'] ?? null,
            telefone: $texto('telefone'),
            cargo: $texto('cargo'),
        );
    }
}
