<?php

declare(strict_types=1);

namespace HT2ML\Core\Http\Middleware;

use Closure;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Tenancy\TenantContext;
use HT2ML\Core\Support\Tenancy\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hidrata o TenantContext (empresa/filial ativas) a cada requisição admin
 * autenticada, revalidando o acesso do usuário ao contexto da sessão. Se a
 * empresa salva não for mais acessível, cai para a empresa padrão; se a filial
 * não for acessível, é limpa. Roda após a autenticação e antes das checagens de
 * acesso (que leem o contexto via AccessCache).
 */
final class DefinirContextoTenant
{
    public function __construct(private readonly TenantResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user();

        if ($user instanceof AdminUser) {
            $this->sincronizar($user, app(TenantContext::class));
        }

        return $next($request);
    }

    private function sincronizar(AdminUser $user, TenantContext $context): void
    {
        $empresaId = $context->empresaAtivaId();

        if ($empresaId === null || ! $this->resolver->podeAcessar($user, $empresaId)) {
            // definirEmpresa já zera a filial — não precisa revalidá-la.
            $context->definirEmpresa($this->resolver->empresaPadrao($user));

            return;
        }

        $filialId = $context->filialAtivaId();

        if ($filialId !== null && ! $user->temAcessoAFilial($filialId)) {
            $context->definirFilial(null);
        }
    }
}
