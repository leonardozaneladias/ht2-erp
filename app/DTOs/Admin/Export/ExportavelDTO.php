<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Export;

/**
 * Dados de uma tabela prontos para exportação (PDF/futuros formatos).
 * Transporte simples entre o componente (que sabe montar as linhas) e a Action
 * de exportação (que só renderiza) — §5.5/§5.6.
 */
final readonly class ExportavelDTO
{
    /**
     * @param  list<string>  $colunas  cabeçalhos
     * @param  list<list<string>>  $linhas  células já formatadas (texto)
     */
    public function __construct(
        public string $titulo,
        public array $colunas,
        public array $linhas,
    ) {}
}
