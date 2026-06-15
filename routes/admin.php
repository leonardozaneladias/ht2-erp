<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

$placeholder = static fn (string $titulo): Response => response(
    "Placeholder da rota admin: {$titulo}.",
    200,
    ['Content-Type' => 'text/plain; charset=UTF-8'],
);

// ── Dev: somente em ambiente local ────────────────────────────────────────
if (app()->isLocal()) {
    Route::redirect('/admin/dev', '/admin/dev/components');

    Route::prefix('admin/dev/components')->name('admin.dev.components.')->group(function (): void {
        Route::view('/', 'admin.dev.components.index')->name('index');
        Route::view('/alert', 'admin.dev.components.alert')->name('alert');
        Route::view('/badge', 'admin.dev.components.badge')->name('badge');
        Route::view('/button', 'admin.dev.components.button')->name('button');
        Route::view('/card', 'admin.dev.components.card')->name('card');
        Route::view('/breadcrumb', 'admin.dev.components.breadcrumb')->name('breadcrumb');
        Route::view('/dropdown', 'admin.dev.components.dropdown')->name('dropdown');
        Route::view('/drawer', 'admin.dev.components.drawer')->name('drawer');
        Route::view('/collapse', 'admin.dev.components.collapse')->name('collapse');
        Route::view('/toast', 'admin.dev.components.toast')->name('toast');
        Route::view('/empty-state', 'admin.dev.components.empty-state')->name('empty-state');
        Route::view('/loading-button', 'admin.dev.components.loading-button')->name('loading-button');
        Route::view('/status-badge', 'admin.dev.components.status-badge')->name('status-badge');
        Route::view('/tabs', 'admin.dev.components.tabs')->name('tabs');
        Route::view('/confirm-dialog', 'admin.dev.components.confirm-dialog')->name('confirm-dialog');
        Route::view('/input', 'admin.dev.components.input')->name('input');
        Route::view('/textarea', 'admin.dev.components.textarea')->name('textarea');
        Route::view('/select', 'admin.dev.components.select')->name('select');
        Route::view('/checkbox', 'admin.dev.components.checkbox')->name('checkbox');
        Route::view('/radio', 'admin.dev.components.radio')->name('radio');
        Route::view('/toggle', 'admin.dev.components.toggle')->name('toggle');
        Route::view('/password-input', 'admin.dev.components.password-input')->name('password-input');
        Route::view('/date-picker', 'admin.dev.components.date-picker')->name('date-picker');
        Route::view('/select-search', 'admin.dev.components.select-search')->name('select-search');
        Route::view('/color-picker', 'admin.dev.components.color-picker')->name('color-picker');
        Route::view('/rich-editor', 'admin.dev.components.rich-editor')->name('rich-editor');
        Route::view('/tags-input', 'admin.dev.components.tags-input')->name('tags-input');
        Route::view('/date-range-picker', 'admin.dev.components.date-range-picker')->name('date-range-picker');
        Route::view('/cpf-input', 'admin.dev.components.cpf-input')->name('cpf-input');
        Route::view('/cnpj-input', 'admin.dev.components.cnpj-input')->name('cnpj-input');
        Route::view('/phone-input', 'admin.dev.components.phone-input')->name('phone-input');
        Route::view('/money-input', 'admin.dev.components.money-input')->name('money-input');
        Route::view('/cep-input', 'admin.dev.components.cep-input')->name('cep-input');
        Route::view('/file-upload', 'admin.dev.components.file-upload')->name('file-upload');
        Route::view('/layout', 'admin.dev.components.layout')->name('layout');
        Route::view('/sidebar', 'admin.dev.components.sidebar')->name('sidebar');
        Route::view('/topbar', 'admin.dev.components.topbar')->name('topbar');
        Route::view('/page-header', 'admin.dev.components.page-header')->name('page-header');
        Route::view('/footer', 'admin.dev.components.footer')->name('footer');
        Route::view('/pagination', 'admin.dev.components.pagination')->name('pagination');
        Route::view('/spinner', 'admin.dev.components.spinner')->name('spinner');
        Route::view('/skeleton', 'admin.dev.components.skeleton')->name('skeleton');
        Route::view('/data-table', 'admin.dev.components.data-table')->name('data-table');
        Route::view('/list-group', 'admin.dev.components.list-group')->name('list-group');
        Route::view('/static-table', 'admin.dev.components.static-table')->name('static-table');
        Route::view('/timeline-table', 'admin.dev.components.timeline-table')->name('timeline-table');
        Route::view('/row-actions', 'admin.dev.components.row-actions')->name('row-actions');
        Route::view('/kpi-card', 'admin.dev.components.kpi-card')->name('kpi-card');
        Route::view('/chart-card', 'admin.dev.components.chart-card')->name('chart-card');
        Route::view('/chart-bar', 'admin.dev.components.chart-bar')->name('chart-bar');
        Route::view('/chart-line', 'admin.dev.components.chart-line')->name('chart-line');
        Route::view('/chart-column', 'admin.dev.components.chart-column')->name('chart-column');
        Route::view('/chart-pie', 'admin.dev.components.chart-pie')->name('chart-pie');
        Route::view('/progress-bar', 'admin.dev.components.progress-bar')->name('progress-bar');
        Route::view('/accordion', 'admin.dev.components.accordion')->name('accordion');
        Route::view('/modal', 'admin.dev.components.modal')->name('modal');
        Route::view('/tooltip', 'admin.dev.components.tooltip')->name('tooltip');
        Route::view('/sortable-list', 'admin.dev.components.sortable-list')->name('sortable-list');
        Route::view('/copy-button', 'admin.dev.components.copy-button')->name('copy-button');
        Route::view('/password-strength-meter', 'admin.dev.components.password-strength-meter')->name('password-strength-meter');
        Route::view('/avatar-cropper', 'admin.dev.components.avatar-cropper')->name('avatar-cropper');
    });

    Route::view('/admin/dev/livewire', 'admin.dev.livewire')->name('admin.dev.livewire');
}

// ── Setup Wizard (público enquanto a instalação não foi concluída) ──────────
Route::get('/admin/setup', App\Livewire\Admin\Setup\SetupWizard::class)->name('admin.setup');

// ── Auth (redireciona para o setup enquanto não instalado) ──────────────────
Route::prefix('admin')->name('admin.')->middleware(App\Http\Middleware\EnsureSystemConfigured::class)->group(function (): void {
    Route::get('/login', App\Livewire\Admin\Auth\Login::class)->name('login');
    Route::get('/esqueceu-senha', App\Livewire\Admin\Auth\ForgotPassword::class)->name('password.request');
    Route::get('/resetar-senha/{token}', App\Livewire\Admin\Auth\ResetPassword::class)->name('password.reset');
    Route::get('/convite/{token}', App\Livewire\Admin\Auth\AceitarConvite::class)->name('convite.aceitar');
    Route::get('/two-factor-challenge', App\Livewire\Admin\Auth\TwoFactorChallenge::class)->name('two-factor-challenge');
});

// ── Admin autenticado (setup tem precedência sobre o login) ─────────────────
Route::prefix('admin')->name('admin.')->middleware([App\Http\Middleware\EnsureSystemConfigured::class, 'admin.auth', Illuminate\Session\Middleware\AuthenticateSession::class, App\Http\Middleware\GarantirContaAtiva::class, App\Http\Middleware\EncerrarImpersonationExpirada::class, App\Http\Middleware\CheckInactivity::class, App\Http\Middleware\EnsureTwoFactorEnabled::class, App\Http\Middleware\DefinirContextoTenant::class, App\Http\Middleware\AplicarPreferenciasUsuario::class])->group(function (): void {
    Route::redirect('/', '/admin/dashboard');

    Route::post('/logout', LogoutController::class)->name('logout');
    Route::post('/impersonation/sair', [App\Http\Controllers\Admin\ImpersonationController::class, 'sair'])
        ->name('impersonation.sair');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/conta', App\Livewire\Admin\Conta\MinhaConta::class)->name('conta');

    Route::prefix('perfil')->name('perfil.')->group(function (): void {
        Route::redirect('/', '/admin/conta')->name('show');
    });

    Route::prefix('conta')->name('conta.')->group(function (): void {
        Route::redirect('/editar', '/admin/conta')->name('edit');
        Route::redirect('/seguranca', '/admin/conta?aba=seguranca')->name('seguranca');
        Route::get('/notificacoes', App\Livewire\Admin\Notificacoes\MinhasNotificacoes::class)->name('notificacoes');
    });

    Route::prefix('empresas')->name('empresas.')->group(function (): void {
        Route::get('/', App\Livewire\Admin\Empresas\IndexEmpresas::class)->name('index');
        Route::get('/nova', App\Livewire\Admin\Empresas\FormEmpresa::class)->name('create');
        Route::get('/{empresa}/editar', App\Livewire\Admin\Empresas\FormEmpresa::class)->name('edit');
    });

    Route::prefix('usuarios')->name('usuarios.')->group(function (): void {
        Route::get('/', App\Livewire\Admin\Usuarios\IndexUsuarios::class)->name('index');
        Route::get('/novo', App\Livewire\Admin\Usuarios\FormUsuario::class)->name('create');
        Route::get('/{usuario}/editar', App\Livewire\Admin\Usuarios\FormUsuario::class)->name('edit');
        Route::get('/{usuario}/lgpd/json', [App\Http\Controllers\Admin\LgpdController::class, 'exportarJson'])->name('lgpd.json');
        Route::get('/{usuario}/lgpd/pdf', [App\Http\Controllers\Admin\LgpdController::class, 'exportarPdf'])->name('lgpd.pdf');
    });

    // Rotas legadas de perfis — consolidadas no hub de Controle de Acesso.
    Route::prefix('perfis')->name('perfis.')->group(function (): void {
        Route::redirect('/', '/admin/acesso')->name('index');
        Route::redirect('/novo', '/admin/acesso')->name('create');
        Route::redirect('/{perfil}/editar', '/admin/acesso')->name('edit');
    });

    Route::prefix('acesso')->name('acesso.')->group(function (): void {
        Route::get('/', App\Livewire\Admin\Acesso\ControleAcesso::class)->name('index');
        // Telas antigas (matriz, simulador, histórico) absorvidas pelo hub.
        Route::redirect('/matriz', '/admin/acesso')->name('matriz');
        Route::redirect('/simulador', '/admin/acesso')->name('simulador');
        Route::redirect('/historico', '/admin/acesso')->name('historico');
    });

    Route::prefix('auditoria')->name('auditoria.')->group(function (): void {
        Route::get('/', App\Livewire\Admin\Auditoria\IndexAuditoria::class)->name('index');
    });

    Route::prefix('configuracoes')->name('configuracoes.')->group(function (): void {
        Route::get('/', App\Livewire\Admin\Configuracao\ConfiguracaoSistema::class)->name('index');
    });

    Route::prefix('menus')->name('menus.')->group(function (): void {
        Route::get('/', App\Livewire\Admin\Menus\GestaoMenus::class)->name('index');
    });

    Route::get('/comunicados', App\Livewire\Admin\Notificacoes\EnviarComunicado::class)->name('comunicados');

    Route::prefix('exemplos')->name('exemplos.')->group(function (): void {
        Route::get('/', App\Livewire\Admin\Exemplos\IndexExemplo::class)->name('index');
        Route::get('/criar', App\Livewire\Admin\Exemplos\FormExemplo::class)->name('create');
        Route::get('/{exemplo}/editar', App\Livewire\Admin\Exemplos\FormExemplo::class)->name('edit');
    });

    // Rotas contribuídas por módulos-pacote (ADR-0015). Cada pacote registra seu
    // callback em App\Support\Modules\ModuleRegistry no register() do provider;
    // aqui elas entram no grupo autenticado, herdando todo o middleware admin.
    foreach (App\Support\Modules\ModuleRegistry::routeCallbacks() as $registrarRotasDoModulo) {
        $registrarRotasDoModulo();
    }

    // Adicione aqui as rotas do seu módulo de negócio
});
