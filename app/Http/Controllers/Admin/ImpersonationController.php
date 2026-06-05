<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Impersonation\EncerrarImpersonationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class ImpersonationController extends Controller
{
    public function sair(EncerrarImpersonationAction $action): RedirectResponse
    {
        $action->execute();

        return redirect()->route('admin.dashboard');
    }
}
