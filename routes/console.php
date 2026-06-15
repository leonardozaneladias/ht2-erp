<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expurga diariamente os logs de auditoria além do teto de retenção
// (config activitylog.clean_after_days). Requer `php artisan schedule:run` no cron.
Schedule::command('activitylog:clean')->daily();

// Manutenção recorrente da plataforma (cada instalação cliente).
Schedule::command('access:expirar')->hourly();                        // revoga concessões diretas vencidas
Schedule::command('auth:clear-resets')->daily();                      // tokens de reset de senha expirados
Schedule::command('queue:prune-failed', ['--hours' => 168])->daily(); // failed jobs com mais de 7 dias
Schedule::command('horizon:snapshot')->everyFiveMinutes();            // snapshot de métricas do Horizon

// Backup diário (spatie/laravel-backup). Ajuste destino/retenção em config/backup.php.
Schedule::command('backup:clean')->daily()->at('01:00');              // remove backups além da retenção
Schedule::command('backup:run')->daily()->at('01:30');                // backup de banco + arquivos
