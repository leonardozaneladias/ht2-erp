<?php

declare(strict_types=1);

use App\Services\Admin\Menu\MenuService;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

afterEach(function (): void {
    ModuleRegistry::flush();
});

it('acumula, expõe e limpa callbacks de rota de módulos', function () {
    ModuleRegistry::flush();
    expect(ModuleRegistry::routeCallbacks())->toBe([]);

    ModuleRegistry::routes(fn () => null);
    ModuleRegistry::routes(fn () => null);
    expect(ModuleRegistry::routeCallbacks())->toHaveCount(2);

    ModuleRegistry::flush();
    expect(ModuleRegistry::routeCallbacks())->toBe([]);
});

it('registra rotas de módulo dentro do grupo admin, herdando o middleware', function () {
    ModuleRegistry::flush();
    ModuleRegistry::routes(function (): void {
        Route::prefix('modulo-fake')->name('modulo-fake.')->group(function (): void {
            Route::get('/', fn () => response('ok'))->name('index');
        });
    });

    // Replica o que o grupo autenticado de routes/admin.php passou a fazer:
    // executar os callbacks dos módulos dentro do middleware admin.
    Route::prefix('admin')->name('admin.')->middleware(['web', 'admin.auth'])->group(function (): void {
        foreach (ModuleRegistry::routeCallbacks() as $registrarRotasDoModulo) {
            $registrarRotasDoModulo();
        }
    });

    // Rotas registradas em runtime exigem reconstruir o lookup de nomes.
    app('router')->getRoutes()->refreshNameLookups();

    expect(Route::has('admin.modulo-fake.index'))->toBeTrue();

    // Sem autenticação, o middleware admin bloqueia (redireciona ao login).
    $this->get('/admin/modulo-fake')->assertRedirect();

    // Autenticado no guard admin, a rota do módulo responde.
    $this->actingAs(criarAdminUser(), 'admin')
        ->get('/admin/modulo-fake')
        ->assertOk()
        ->assertSee('ok');
});

it('publica permissões de módulo via merge de config, sem editar o core', function () {
    config(['access.modules' => array_merge_recursive(
        config('access.modules', []),
        ['negocio' => ['modulo_fake.listar' => ['label' => 'Listar fake', 'descricao' => 'Teste de módulo.']]],
    )]);

    Artisan::call('access:sync');

    expect(
        Permission::query()->where('name', 'modulo_fake.listar')->where('guard_name', 'admin')->exists(),
    )->toBeTrue();
});

it('expõe itens de menu de módulo na sidebar via merge de config, sem editar o core', function () {
    $menu = config('admin-menu', []);
    foreach ($menu as $i => $secao) {
        if (($secao['key'] ?? null) === 'negocio') {
            $menu[$i]['items'][] = [
                'key' => 'modulo-fake',
                'label' => 'Módulo Fake',
                'icon' => 'tabler--folder',
                'route' => 'admin.dashboard',
                'permission' => null,
                'active' => ['admin.modulo-fake.*'],
            ];
        }
    }
    config(['admin-menu' => $menu]);

    $menuService = app(MenuService::class);
    $menuService->invalidarCache();

    $estrutura = $menuService->estruturaParaSidebar(criarAdminUser(), mostrarTudo: true);

    $chaves = collect($estrutura)->pluck('items')->flatten(1)->pluck('key');
    expect($chaves)->toContain('modulo-fake');
});
