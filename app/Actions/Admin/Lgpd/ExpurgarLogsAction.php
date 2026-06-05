<?php

declare(strict_types=1);

namespace App\Actions\Admin\Lgpd;

use Illuminate\Support\Facades\Artisan;

/**
 * Expurga (sob demanda) os registros de auditoria mais antigos que o teto de
 * retenção (config activitylog.clean_after_days, derivado de dias_retencao_logs).
 */
final class ExpurgarLogsAction
{
    public function execute(): void
    {
        Artisan::call('activitylog:clean');

        activity('lgpd')
            ->causedBy(auth('admin')->user())
            ->event('logs-expurgados')
            ->log('Logs de auditoria antigos expurgados (LGPD)');
    }
}
