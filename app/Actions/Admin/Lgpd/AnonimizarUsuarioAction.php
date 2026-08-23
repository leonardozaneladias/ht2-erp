<?php

declare(strict_types=1);

namespace App\Actions\Admin\Lgpd;

use App\Exceptions\AccessException;
use App\Services\Admin\HierarchyResolver;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Anonimiza irreversivelmente a PII de um usuário (direito ao esquecimento LGPD):
 * sobrescreve dados pessoais com valores neutros, embaralha a senha, revoga acessos
 * e marca `anonimizado_em`. Mantém a linha + o activity_log, mas mascara a PII
 * que ficou nos logs antigos (MascararAtividadesUsuarioAction).
 */
final class AnonimizarUsuarioAction
{
    public function __construct(
        private readonly HierarchyResolver $hierarchy,
        private readonly MascararAtividadesUsuarioAction $mascararAtividades,
    ) {}

    public function execute(AdminUser $ator, AdminUser $alvo): void
    {
        $this->garantirElegivel($ator, $alvo);

        DB::transaction(function () use ($ator, $alvo): void {
            $emailOriginal = $alvo->email;
            // Sem isto o trait Auditavel gravaria a PII original em
            // properties.old — derrotando a anonimização.
            $alvo->disableLogging();

            $alvo->forceFill([
                'nome' => 'Usuário anonimizado',
                'email' => 'anonimizado-' . $alvo->id . '@removido.local',
                'password' => Hash::make(Str::random(40)),
                'avatar_url' => null,
                'last_login_ip' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'bloqueado_ate' => null,
                'perfil_ativo_role_id' => null,
                'empresa_ativa_id' => null,
                'filial_ativa_id' => null,
                'ativo' => false,
                'anonimizado_em' => now(),
            ])->save();

            $alvo->syncRoles([]);
            $alvo->empresasAcessiveis()->detach();
            $alvo->filiaisAcessiveis()->detach();
            $alvo->papeisPorEmpresa()->detach();
            $alvo->permissionGrants()->delete();

            // Logs antigos guardam a PII em diffs/properties — mascarar é parte
            // do direito ao esquecimento (exceção sancionada ao append-only).
            $mascaradas = $this->mascararAtividades->execute($alvo, $emailOriginal);

            activity('lgpd')
                ->causedBy($ator)
                ->performedOn($alvo)
                ->event('anonimizado')
                ->withProperties(['atividades_mascaradas' => $mascaradas])
                ->log('Usuário anonimizado (LGPD)');
        });
    }

    private function garantirElegivel(AdminUser $ator, AdminUser $alvo): void
    {
        if ($ator->is($alvo)) {
            throw new AccessException('Você não pode anonimizar a si mesmo.');
        }

        if ($alvo->estaAnonimizado()) {
            throw new AccessException('Este usuário já foi anonimizado.');
        }

        if ($this->ehSuperAdmin($alvo)) {
            throw new AccessException('Não é possível anonimizar um super-administrador.');
        }

        if (! $this->hierarchy->podeGerir($ator, $alvo)) {
            throw new AccessException('Você não tem hierarquia para anonimizar este usuário.');
        }
    }

    private function ehSuperAdmin(AdminUser $user): bool
    {
        $user->loadMissing('roles');

        return $user->roles->contains('name', (string) config('access.super_admin_role', 'super-admin'));
    }
}
