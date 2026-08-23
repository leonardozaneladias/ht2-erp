<?php

declare(strict_types=1);

use App\Actions\Admin\Lgpd\ExpurgarLogsAction;
use HT2ML\Core\Models\Activity;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aplica dias_retencao_logs ao config do activitylog', function (): void {
    $s = app(SegurancaSettings::class);
    $s->dias_retencao_logs = 30;
    $s->save();

    app(HT2ML\Core\Services\Admin\Settings\SettingsRuntimeApplier::class)->apply();

    expect(config('activitylog.clean_after_days'))->toBe(30);
});

it('expurga atividades mais antigas que o teto e mantém as recentes', function (): void {
    $s = app(SegurancaSettings::class);
    $s->dias_retencao_logs = 30;
    $s->save();
    config(['activitylog.clean_after_days' => 30]);

    $recente = activity('test')->log('recente');
    $antiga = activity('test')->log('antiga');
    Activity::query()->whereKey($antiga->id)->update(['created_at' => now()->subDays(60)]);

    app(ExpurgarLogsAction::class)->execute();

    expect(Activity::query()->whereKey($antiga->id)->exists())->toBeFalse()
        ->and(Activity::query()->whereKey($recente->id)->exists())->toBeTrue();
});
