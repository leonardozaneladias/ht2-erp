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
    ) {}

    /**
     * @param  array{nome: string, email: string, ativo?: bool, roles?: list<string>, password?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nome: $data['nome'],
            email: $data['email'],
            ativo: $data['ativo'] ?? true,
            roles: $data['roles'] ?? [],
            password: $data['password'] ?? null,
        );
    }
}
