<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin;

final readonly class DashboardMetricsDTO
{
    /**
     * @param  list<string>  $categorias  Rótulos do eixo X (meses) do gráfico.
     * @param  list<int>  $serie  Novos usuários admin por mês (mesma ordem de $categorias).
     */
    public function __construct(
        public int $totalUsuarios,
        public int $usuariosAtivos,
        public int $totalEmpresas,
        public int $eventosHoje,
        public array $categorias,
        public array $serie,
    ) {}
}
