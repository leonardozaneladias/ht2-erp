<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

final readonly class PerfilContaDTO
{
    public function __construct(
        public string $nome,
        public ?string $telefone = null,
        public ?string $cargo = null,
        public ?string $bio = null,
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
            telefone: $texto('telefone'),
            cargo: $texto('cargo'),
            bio: $texto('bio'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paraModel(): array
    {
        return [
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'cargo' => $this->cargo,
            'bio' => $this->bio,
        ];
    }
}
