<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\ConcessaoAcessoDTO;
use App\Services\Admin\AccessResolver;
use App\Support\Access\AccessGuard;
use App\Support\Access\PermissionRegistry;
use HT2ML\Core\Enums\TipoConcessao;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\PermissionGrant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;

class ConcederAcessoDiretoAction
{
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly AccessResolver $resolver,
        private readonly PermissionRegistry $registry,
    ) {}

    public function execute(ConcessaoAcessoDTO $dto, ?AdminUser $ator = null): PermissionGrant
    {
        if (! $this->registry->existe($dto->permissao)) {
            throw new InvalidArgumentException("Permissão desconhecida: {$dto->permissao}.");
        }

        $ator = $this->resolverAtor($ator);

        return DB::transaction(function () use ($dto, $ator): PermissionGrant {
            $alvo = AdminUser::findOrFail($dto->adminUserId);

            if ($ator instanceof AdminUser) {
                $this->guard->garantirHierarquiaSobreUsuario($ator, $alvo);
            }

            if ($dto->tipo === TipoConcessao::Deny) {
                $this->guard->garantirDenyNaoEmSuperAdmin($alvo);
            }

            $permission = Permission::query()
                ->where('name', $dto->permissao)
                ->where('guard_name', 'admin')
                ->firstOrFail();

            // Upsert lógico: revoga a concessão viva equivalente antes de criar nova.
            $alvo->permissionGrants()
                ->vigentes()
                ->doTipo($dto->tipo)
                ->where('permission_id', $permission->getKey())
                ->update(['revoked_at' => now()]);

            $grant = PermissionGrant::create([
                'admin_user_id' => $alvo->getKey(),
                'permission_id' => $permission->getKey(),
                'type' => $dto->tipo,
                'reason' => $dto->motivo,
                'expires_at' => $dto->expiraEm,
                'granted_by' => $ator instanceof AdminUser ? $ator->getKey() : null,
                'contexto_tipo' => $dto->contextoTipo,
                'contexto_id' => $dto->contextoId,
            ]);

            activity('acessos')
                ->performedOn($alvo)
                ->causedBy($ator)
                ->withProperties([
                    'permissao' => $dto->permissao,
                    'tipo' => $dto->tipo->value,
                    'expira_em' => $dto->expiraEm,
                    'motivo' => $dto->motivo,
                ])
                ->event($dto->tipo === TipoConcessao::Deny ? 'acesso_negado' : 'acesso_concedido')
                ->log($dto->tipo === TipoConcessao::Deny ? 'Acesso negado diretamente' : 'Acesso concedido diretamente');

            $this->resolver->invalidar($alvo);

            return $grant;
        });
    }

    private function resolverAtor(?AdminUser $ator): ?AdminUser
    {
        if ($ator instanceof AdminUser) {
            return $ator;
        }

        $atual = Auth::guard('admin')->user();

        return $atual instanceof AdminUser ? $atual : null;
    }
}
