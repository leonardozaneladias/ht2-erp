<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Models\PermissionGrant;
use HT2ML\Core\Policies\AdminUserPolicy;
use HT2ML\Core\Policies\EmpresaPolicy;
use HT2ML\Core\Policies\PermissionGrantPolicy;
use HT2ML\Core\Policies\RolePolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Spatie\Permission\Models\Role;

/**
 * Guarda contra uma regressão que já aconteceu.
 *
 * A descoberta por convenção do Laravel mapeia App\Models\X para
 * App\Policies\XPolicy. Quando Empresa saiu de App\Models para ht2ml/core, a
 * convenção passou a procurar HT2ML\Core\Policies\EmpresaPolicy — que ainda não
 * existia — e a EmpresaPolicy deixou de ser aplicada SEM ERRO NENHUM. Um
 * controle de autorização desligado em silêncio.
 *
 * Mover um model entre namespaces não pode desligar sua policy de novo.
 */
it('resolve a policy de cada model do core', function (string $model, string $policy) {
    $resolvida = app(Gate::class)->getPolicyFor($model);

    expect($resolvida)->not->toBeNull(
        "Nenhuma policy resolvida para {$model}. Se o model mudou de namespace, "
        . 'declare a policy em CoreServiceProvider::registrarPolicies().',
    )->and($resolvida)->toBeInstanceOf($policy);
})->with([
    'AdminUser' => [AdminUser::class, AdminUserPolicy::class],
    'Empresa' => [Empresa::class, EmpresaPolicy::class],
    'PermissionGrant' => [PermissionGrant::class, PermissionGrantPolicy::class],
    'Role' => [Role::class, RolePolicy::class],
]);

it('não deixa policy do core órfã: toda policy do pacote está registrada', function () {
    $arquivos = glob(base_path('packages/core/src/Policies/*Policy.php')) ?: [];

    $declaradas = collect($arquivos)
        ->map(fn (string $f): string => 'HT2ML\\Core\\Policies\\' . basename($f, '.php'))
        ->all();

    $registradas = collect([AdminUser::class, Empresa::class, PermissionGrant::class, Role::class])
        ->map(fn (string $m): ?string => ($p = app(Gate::class)->getPolicyFor($m)) ? $p::class : null)
        ->filter()
        ->all();

    expect(array_diff($declaradas, $registradas))->toBe(
        [],
        'Há policy em packages/core/src/Policies que ninguém registra — '
        . 'ela não está sendo aplicada. Declare em CoreServiceProvider::registrarPolicies().',
    );
});
