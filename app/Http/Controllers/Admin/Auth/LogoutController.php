<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuditoriaSeguranca;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutController extends Controller
{
    public function __invoke(Request $request, ImpersonationContext $context): RedirectResponse
    {
        $usuario = Auth::guard('admin')->user();

        if ($context->ativo()) {
            $originalId = $context->originalId();
            $original = $originalId !== null ? AdminUser::find($originalId) : null;
            $context->encerrar();

            activity('impersonation')
                ->causedBy($original)
                ->performedOn($usuario)
                ->event('encerrada')
                ->log('Personificação encerrada (logout)');
        } elseif ($usuario instanceof AdminUser) {
            app(AuditoriaSeguranca::class)->logout($usuario);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
