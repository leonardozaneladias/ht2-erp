<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Support\Impersonation\ImpersonationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutController extends Controller
{
    public function __invoke(Request $request, ImpersonationContext $context): RedirectResponse
    {
        if ($context->ativo()) {
            $originalId = $context->originalId();
            $original = $originalId !== null ? AdminUser::find($originalId) : null;
            $context->encerrar();

            activity('impersonation')
                ->causedBy($original)
                ->performedOn(Auth::guard('admin')->user())
                ->event('encerrada')
                ->log('Personificação encerrada (logout)');
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
