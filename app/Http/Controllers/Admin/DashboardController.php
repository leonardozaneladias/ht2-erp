<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardMetrics;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(DashboardMetrics $metrics): View
    {
        return view('admin.dashboard.index', ['metricas' => $metrics->obter()]);
    }
}
