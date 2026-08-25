<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin;

use HT2ML\Core\Support\Access\AreaDeAcesso;

final readonly class PermissionDefinitionDTO
{
    public function __construct(
        public string $nome,
        public AreaDeAcesso $area,
        public string $label,
        public ?string $descricao = null,
    ) {}

    /**
     * @param  array{nome: string, area: string|AreaDeAcesso, label: string, descricao?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        // AreaDeAcesso::de() nunca lança para chave desconhecida — de propósito.
        // Aqui isto é a diferença entre "uma extensão instalada com a área
        // trocada" e "a tela de acesso inteira caiu": o catálogo continua
        // legível, e ht2ml:doutor aponta a área não-declarada pelo nome.
        return new self(
            nome: $data['nome'],
            area: $data['area'] instanceof AreaDeAcesso ? $data['area'] : AreaDeAcesso::de($data['area']),
            label: $data['label'],
            descricao: $data['descricao'] ?? null,
        );
    }
}
