<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Lgpd\ExportarDadosUsuarioAction;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class LgpdController extends Controller
{
    public function exportarJson(AdminUser $usuario, ExportarDadosUsuarioAction $action): JsonResponse
    {
        Gate::authorize('exportarDados', $usuario);

        $dados = $action->execute($usuario);
        $this->auditar($usuario);

        return response()->json($dados, 200, [
            'Content-Disposition' => 'attachment; filename="dados-usuario-' . $usuario->id . '.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function exportarPdf(AdminUser $usuario, ExportarDadosUsuarioAction $action): Response
    {
        Gate::authorize('exportarDados', $usuario);

        $dados = $action->execute($usuario);
        $this->auditar($usuario);

        return Pdf::loadView('admin.lgpd.export', ['dados' => $dados])
            ->download('dados-usuario-' . $usuario->id . '.pdf');
    }

    private function auditar(AdminUser $usuario): void
    {
        activity('lgpd')
            ->causedBy(auth('admin')->user())
            ->performedOn($usuario)
            ->event('exportado')
            ->log('Dados pessoais exportados (LGPD)');
    }
}
