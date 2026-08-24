<?php

declare(strict_types=1);

namespace HT2ML\Core\Services\Admin;

use HT2ML\Core\DTOs\Admin\DashboardMetricsDTO;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * Métricas reais do dashboard (sem dados de negócio inventados): contagens de
 * infraestrutura do starter kit e a série de novos usuários por mês.
 *
 * API-ready: não recebe Request e retorna um DTO; quem formata é a view.
 */
final class DashboardMetrics
{
    private const MESES_NA_SERIE = 6;

    public function obter(): DashboardMetricsDTO
    {
        [$categorias, $serie] = $this->novosUsuariosPorMes();

        return new DashboardMetricsDTO(
            totalUsuarios: AdminUser::query()->count(),
            usuariosAtivos: AdminUser::query()->where('ativo', true)->count(),
            totalEmpresas: Empresa::query()->count(),
            eventosHoje: Activity::query()->whereDate('created_at', Carbon::today())->count(),
            categorias: $categorias,
            serie: $serie,
        );
    }

    /**
     * Novos usuários admin nos últimos meses. Agregado em PHP (a partir de um
     * pluck de datas) para não depender de funções de data específicas do banco.
     *
     * @return array{0: list<string>, 1: list<int>}
     */
    private function novosUsuariosPorMes(): array
    {
        $inicio = Carbon::now()->startOfMonth()->subMonths(self::MESES_NA_SERIE - 1);

        /** @var array<string, array{label: string, total: int}> $meses */
        $meses = [];

        for ($i = 0; $i < self::MESES_NA_SERIE; $i++) {
            $mes = $inicio->copy()->addMonths($i);
            $meses[$mes->format('Y-m')] = [
                'label' => ucfirst($mes->translatedFormat('M')),
                'total' => 0,
            ];
        }

        AdminUser::query()
            ->where('created_at', '>=', $inicio)
            ->pluck('created_at')
            ->each(function (Carbon $data) use (&$meses): void {
                $chave = $data->format('Y-m');

                if (isset($meses[$chave])) {
                    $meses[$chave]['total']++;
                }
            });

        return [
            array_values(array_map(static fn (array $m): string => $m['label'], $meses)),
            array_values(array_map(static fn (array $m): int => $m['total'], $meses)),
        ];
    }
}
