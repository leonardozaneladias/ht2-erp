<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use HT2ML\Core\Models\AdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Sincroniza a quais empresas um usuário tem acesso (pivot admin_user_empresa),
 * concedendo acesso a todas as filiais de cada empresa. Limpa a empresa ativa do
 * usuário se ela deixar de ser acessível.
 */
class SyncAcessoEmpresaAction
{
    /**
     * @param  list<int>  $empresaIds
     */
    public function execute(AdminUser $usuario, array $empresaIds): AdminUser
    {
        return DB::transaction(function () use ($usuario, $empresaIds): AdminUser {
            $pivot = [];

            foreach (array_values(array_unique($empresaIds)) as $id) {
                $pivot[$id] = ['todas_filiais' => true];
            }

            $usuario->empresasAcessiveis()->sync($pivot);

            // Se a empresa ativa não é mais acessível, zera o contexto persistido.
            if ($usuario->empresa_ativa_id !== null && ! in_array($usuario->empresa_ativa_id, $empresaIds, true)) {
                $usuario->update(['empresa_ativa_id' => null, 'filial_ativa_id' => null]);
            }

            activity('admin_users')
                ->performedOn($usuario)
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties(['empresas' => array_values($empresaIds)])
                ->event('acesso_empresas_sincronizado')
                ->log('Acesso do usuário a empresas atualizado');

            return $usuario->fresh() ?? $usuario;
        });
    }
}
