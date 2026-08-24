<?php

declare(strict_types=1);

use HT2ML\Core\Http\Controllers\Admin\Auth\LogoutController;
use HT2ML\Core\Http\Controllers\Admin\DashboardController;
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
        Route::view('/field-display', 'admin.dev.components.field-display')->name('field-display');
        Route::view('/ficha-drawer', 'admin.dev.components.ficha-drawer')->name('ficha-drawer');
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
        Route::view('/masked-input', 'admin.dev.components.masked-input')->name('masked-input');
        Route::view('/file-upload', 'admin.dev.components.file-upload')->name('file-upload');
        Route::view('/layout', 'admin.dev.components.layout')->name('layout');
        Route::view('/sidebar', 'admin.dev.components.sidebar')->name('sidebar');
        Route::view('/topbar', 'admin.dev.components.topbar')->name('topbar');
        Route::view('/page-header', 'admin.dev.components.page-header')->name('page-header');
        Route::view('/footer', 'admin.dev.components.footer')->name('footer');
        Route::view('/pagination', 'admin.dev.components.pagination')->name('pagination');
        Route::view('/spinner', 'admin.dev.components.spinner')->name('spinner');
        Route::view('/skeleton', 'admin.dev.components.skeleton')->name('skeleton');
        Route::view('/reveal', 'admin.dev.components.reveal')->name('reveal');
        Route::view('/stat-tile', 'admin.dev.components.stat-tile')->name('stat-tile');
        Route::view('/step-guide', 'admin.dev.components.step-guide')->name('step-guide');
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
        Route::view('/wizard', 'admin.dev.components.wizard')->name('wizard');
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
Route::get('/admin/setup', HT2ML\Core\Livewire\Admin\Setup\SetupWizard::class)->name('admin.setup');

// ── Auth (redireciona para o setup enquanto não instalado) ──────────────────
Route::prefix('admin')->name('admin.')->middleware(HT2ML\Core\Http\Middleware\EnsureSystemConfigured::class)->group(function (): void {
    Route::get('/login', HT2ML\Core\Livewire\Admin\Auth\Login::class)->name('login');
    Route::get('/esqueceu-senha', HT2ML\Core\Livewire\Admin\Auth\ForgotPassword::class)->name('password.request');
    Route::get('/resetar-senha/{token}', HT2ML\Core\Livewire\Admin\Auth\ResetPassword::class)->name('password.reset');
    Route::get('/convite/{token}', HT2ML\Core\Livewire\Admin\Auth\AceitarConvite::class)->name('convite.aceitar');
    Route::get('/two-factor-challenge', HT2ML\Core\Livewire\Admin\Auth\TwoFactorChallenge::class)->name('two-factor-challenge');
});

// ── Admin autenticado (setup tem precedência sobre o login) ─────────────────
Route::prefix('admin')->name('admin.')->middleware([HT2ML\Core\Http\Middleware\EnsureSystemConfigured::class, 'admin.auth', Illuminate\Session\Middleware\AuthenticateSession::class, HT2ML\Core\Http\Middleware\GarantirContaAtiva::class, HT2ML\Core\Http\Middleware\EncerrarImpersonationExpirada::class, HT2ML\Core\Http\Middleware\CheckInactivity::class, HT2ML\Core\Http\Middleware\EnsureTwoFactorEnabled::class, HT2ML\Core\Http\Middleware\DefinirContextoTenant::class, HT2ML\Core\Http\Middleware\AplicarPreferenciasUsuario::class])->group(function (): void {
    Route::redirect('/', '/admin/dashboard');

    Route::post('/logout', LogoutController::class)->name('logout');
    Route::post('/impersonation/sair', [HT2ML\Core\Http\Controllers\Admin\ImpersonationController::class, 'sair'])
        ->name('impersonation.sair');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/conta', HT2ML\Core\Livewire\Admin\Conta\MinhaConta::class)->name('conta');

    Route::prefix('perfil')->name('perfil.')->group(function (): void {
        Route::redirect('/', '/admin/conta')->name('show');
    });

    Route::prefix('conta')->name('conta.')->group(function (): void {
        Route::redirect('/editar', '/admin/conta')->name('edit');
        Route::redirect('/seguranca', '/admin/conta?aba=seguranca')->name('seguranca');
        Route::get('/notificacoes', HT2ML\Core\Livewire\Admin\Notificacoes\MinhasNotificacoes::class)->name('notificacoes');
    });

    Route::prefix('empresas')->name('empresas.')->group(function (): void {
        Route::get('/', HT2ML\Core\Livewire\Admin\Empresas\IndexEmpresas::class)->name('index');
        Route::get('/nova', HT2ML\Core\Livewire\Admin\Empresas\FormEmpresa::class)->name('create');
        Route::get('/{empresa}/editar', HT2ML\Core\Livewire\Admin\Empresas\FormEmpresa::class)->name('edit');
    });

    Route::prefix('usuarios')->name('usuarios.')->group(function (): void {
        Route::get('/', HT2ML\Core\Livewire\Admin\Usuarios\IndexUsuarios::class)->name('index');
        Route::get('/novo', HT2ML\Core\Livewire\Admin\Usuarios\FormUsuario::class)->name('create');
        Route::get('/{usuario}/editar', HT2ML\Core\Livewire\Admin\Usuarios\FormUsuario::class)->name('edit');
        Route::get('/{usuario}/lgpd/json', [HT2ML\Core\Http\Controllers\Admin\LgpdController::class, 'exportarJson'])->name('lgpd.json');
        Route::get('/{usuario}/lgpd/pdf', [HT2ML\Core\Http\Controllers\Admin\LgpdController::class, 'exportarPdf'])->name('lgpd.pdf');
    });

    // Rotas legadas de perfis — consolidadas no hub de Controle de Acesso.
    Route::prefix('perfis')->name('perfis.')->group(function (): void {
        Route::redirect('/', '/admin/acesso')->name('index');
        Route::redirect('/novo', '/admin/acesso')->name('create');
        Route::redirect('/{perfil}/editar', '/admin/acesso')->name('edit');
    });

    Route::prefix('acesso')->name('acesso.')->group(function (): void {
        Route::get('/', HT2ML\Core\Livewire\Admin\Acesso\ControleAcesso::class)->name('index');
        // Telas antigas (matriz, simulador, histórico) absorvidas pelo hub.
        Route::redirect('/matriz', '/admin/acesso')->name('matriz');
        Route::redirect('/simulador', '/admin/acesso')->name('simulador');
        Route::redirect('/historico', '/admin/acesso')->name('historico');
    });

    Route::prefix('auditoria')->name('auditoria.')->group(function (): void {
        Route::get('/', HT2ML\Core\Livewire\Admin\Auditoria\IndexAuditoria::class)->name('index');
    });

    Route::prefix('configuracoes')->name('configuracoes.')->group(function (): void {
        Route::get('/', HT2ML\Core\Livewire\Admin\Configuracao\ConfiguracaoSistema::class)->name('index');
    });

    Route::prefix('menus')->name('menus.')->group(function (): void {
        Route::get('/', HT2ML\Core\Livewire\Admin\Menus\GestaoMenus::class)->name('index');
    });

    Route::get('/comunicados', HT2ML\Core\Livewire\Admin\Notificacoes\EnviarComunicado::class)->name('comunicados');

    Route::prefix('exemplos')->name('exemplos.')->group(function (): void {
        Route::get('/', App\Livewire\Admin\Exemplos\IndexExemplo::class)->name('index');
        Route::get('/criar', App\Livewire\Admin\Exemplos\FormExemplo::class)->name('create');
        Route::get('/{exemplo}/editar', App\Livewire\Admin\Exemplos\FormExemplo::class)->name('edit');
    });

    // Tabelas Auxiliares (dados de referência) — CRUD por catálogo.
    Route::prefix('referencia')->name('referencia.')->group(function (): void {
        Route::prefix('estados')->name('estados.')->group(function (): void {
            Route::get('/', HT2ML\Core\Livewire\Admin\Referencia\IndexEstado::class)->name('index');
            Route::get('/criar', HT2ML\Core\Livewire\Admin\Referencia\FormEstado::class)->name('create');
            Route::get('/{estado}/editar', HT2ML\Core\Livewire\Admin\Referencia\FormEstado::class)->name('edit');
        });

        Route::prefix('paises')->name('paises.')->group(function (): void {
            Route::get('/', HT2ML\Core\Livewire\Admin\Referencia\IndexPais::class)->name('index');
            Route::get('/criar', HT2ML\Core\Livewire\Admin\Referencia\FormPais::class)->name('create');
            Route::get('/{pais}/editar', HT2ML\Core\Livewire\Admin\Referencia\FormPais::class)->name('edit');
        });

        Route::prefix('municipios')->name('municipios.')->group(function (): void {
            Route::get('/', HT2ML\Core\Livewire\Admin\Referencia\IndexMunicipio::class)->name('index');
            Route::get('/criar', HT2ML\Core\Livewire\Admin\Referencia\FormMunicipio::class)->name('create');
            Route::get('/{municipio}/editar', HT2ML\Core\Livewire\Admin\Referencia\FormMunicipio::class)->name('edit');
        });

        Route::prefix('moedas')->name('moedas.')->group(function (): void {
            Route::get('/', HT2ML\Core\Livewire\Admin\Referencia\IndexMoeda::class)->name('index');
            Route::get('/criar', HT2ML\Core\Livewire\Admin\Referencia\FormMoeda::class)->name('create');
            Route::get('/{moeda}/editar', HT2ML\Core\Livewire\Admin\Referencia\FormMoeda::class)->name('edit');
        });

        Route::prefix('bancos')->name('bancos.')->group(function (): void {
            Route::get('/', HT2ML\Core\Livewire\Admin\Referencia\IndexBanco::class)->name('index');
            Route::get('/criar', HT2ML\Core\Livewire\Admin\Referencia\FormBanco::class)->name('create');
            Route::get('/{banco}/editar', HT2ML\Core\Livewire\Admin\Referencia\FormBanco::class)->name('edit');
        });

        Route::prefix('cargos')->name('cargos.')->group(function (): void {
            Route::get('/', HT2ML\Core\Livewire\Admin\Referencia\IndexCargo::class)->name('index');
            Route::get('/criar', HT2ML\Core\Livewire\Admin\Referencia\FormCargo::class)->name('create');
            Route::get('/{cargo}/editar', HT2ML\Core\Livewire\Admin\Referencia\FormCargo::class)->name('edit');
        });

        Route::prefix('tipos-logradouro')->name('tipos_logradouro.')->group(function (): void {
            Route::get('/', HT2ML\Core\Livewire\Admin\Referencia\IndexTipoLogradouro::class)->name('index');
            Route::get('/criar', HT2ML\Core\Livewire\Admin\Referencia\FormTipoLogradouro::class)->name('create');
            Route::get('/{tipo_logradouro}/editar', HT2ML\Core\Livewire\Admin\Referencia\FormTipoLogradouro::class)->name('edit');
        });

    });

    // Rotas contribuídas por módulos-pacote (ADR-0015). Cada pacote registra seu
    // callback em HT2ML\Core\Support\Modules\ModuleRegistry no register() do provider;
    // aqui elas entram no grupo autenticado, herdando todo o middleware admin.
    foreach (HT2ML\Core\Support\Modules\ModuleRegistry::routeCallbacks() as $registrarRotasDoModulo) {
        $registrarRotasDoModulo();
    }

    // Adicione aqui as rotas do seu módulo de negócio
});
