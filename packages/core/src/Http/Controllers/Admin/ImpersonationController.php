<?php

declare(strict_types=1);

namespace HT2ML\Core\Http\Controllers\Admin;

use HT2ML\Core\Actions\Admin\Impersonation\EncerrarImpersonationAction;
use HT2ML\Core\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class ImpersonationController extends Controller
{
    public function sair(EncerrarImpersonationAction $action): RedirectResponse
    {
        $action->execute();

        return redirect()->route('admin.dashboard');
    }
}
