<?php

declare(strict_types=1);

namespace HT2ML\Core\Http\Controllers\Admin;

use HT2ML\Core\Http\Controllers\Controller;
use HT2ML\Core\Services\Admin\DashboardMetrics;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(DashboardMetrics $metrics): View
    {
        return view('admin.dashboard.index', ['metricas' => $metrics->obter()]);
    }
}
