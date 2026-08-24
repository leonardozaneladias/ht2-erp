<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.dashboard'));

// Healthcheck público (monitoramento/load-balancer). Complementa o /up nativo.
Route::get('/healthz', HT2ML\Core\Http\Controllers\HealthCheckController::class)->name('healthz');
