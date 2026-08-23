<?php

declare(strict_types=1);

use App\Enums\TipoConcessao;
use App\Models\AdminUser;
use App\Models\PermissionGrant;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

// Extensões: os testes vivem em packages/*/tests, fora da árvore tests/, e por
// isso não são alcançados pelos ->in('Feature'|'Unit') acima. Sem esta linha
// eles simplesmente não rodam — foi o que aconteceu com o módulo de RH desde
// que ele virou pacote.
pest()->extend(TestCase::class)
    ->in(__DIR__ . '/../packages');

// Testes de browser (E2E): rodam no host com Playwright (`make test-e2e`).
// O grupo permite excluí-los nos ambientes sem Chromium (DDEV/CI padrão).
pest()->extend(TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->group('browser')
    ->in('Browser');

// Por padrão, considera o sistema já instalado para não acionar o middleware do
// Setup Wizard nos testes HTTP. Os testes do próprio wizard sobrescrevem isto.
pest()->beforeEach(function () {
    try {
        if (Illuminate\Support\Facades\Schema::hasTable('settings')) {
            marcarInstalado(true);
        }
    } catch (Throwable) {
        // Settings indisponíveis neste teste — nada a fazer.
    }
})->in('Feature', 'Browser', __DIR__ . '/../packages');

// Desabilita o Vite nos testes Feature: eles renderizam views mas não dependem
// de assets buildados (o build real é coberto pelo job de browser). Evita o
// ViteManifestNotFoundException no CI, que não roda `npm run build` no job PHP.
pest()->beforeEach(function () {
    $this->withoutVite();
})->in('Feature', __DIR__ . '/../packages');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/**
 * Cria um AdminUser de teste (delega para a AdminUserFactory).
 */
function criarAdminUser(string $email = 'user@teste.com', bool $ativo = true): AdminUser
{
    return AdminUser::factory()->create([
        'nome' => 'Usuário Teste',
        'email' => $email,
        'ativo' => $ativo,
    ]);
}

/**
 * Cria uma concessão ou negação direta de permissão para um usuário (guard admin).
 */
function concederAcessoDireto(
    AdminUser $user,
    string $permissao,
    TipoConcessao $tipo,
    ?string $expiraEm = null,
): PermissionGrant {
    $permission = Permission::query()
        ->where('name', $permissao)
        ->where('guard_name', 'admin')
        ->firstOrFail();

    return PermissionGrant::create([
        'admin_user_id' => $user->id,
        'permission_id' => $permission->id,
        'type' => $tipo,
        'reason' => 'teste',
        'expires_at' => $expiraEm,
    ]);
}

/**
 * Cria (ou recupera) uma role do guard admin com um nível hierárquico.
 */
function criarRoleAdmin(string $name, int $nivel): Spatie\Permission\Models\Role
{
    $role = Spatie\Permission\Models\Role::findOrCreate($name, 'admin');
    $role->forceFill(['nivel' => $nivel])->save();

    return $role;
}

/**
 * Marca (ou desmarca) o sistema como instalado para os testes.
 */
function marcarInstalado(bool $instalado = true): void
{
    $settings = app(App\Settings\GeneralSettings::class);
    $settings->instalado = $instalado;
    $settings->save();
}
