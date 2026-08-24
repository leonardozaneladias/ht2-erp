<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin;

final readonly class ImportResultadoDTO
{
    public function __construct(
        public int $totalLinhas,
        public int $linhasImportadas,
        public int $linhasComErro,
        /** @var list<array{linha: int, campo: string, mensagem: string}> */
        public array $erros,
    ) {}
}
