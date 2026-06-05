<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('usa o model custom e resolve a relação empresa do activity_log', function (): void {
    expect(config('activitylog.activity_model'))->toBe(Activity::class);

    $empresa = Empresa::create(['nome' => 'Acme', 'ativo' => true]);

    $log = activity('test')->log('evento');
    expect($log)->toBeInstanceOf(Activity::class);

    $log->empresa_id = $empresa->id;
    $log->save();

    $fresh = Activity::findOrFail($log->id);
    expect($fresh->empresa)->not->toBeNull()
        ->and($fresh->empresa->nome)->toBe('Acme');
});
