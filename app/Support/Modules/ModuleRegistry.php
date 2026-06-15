<?php

declare(strict_types=1);

namespace App\Support\Modules;

use Closure;

/**
 * Registro central de contribuições de módulos-pacote ao core (ver ADR-0015).
 *
 * Módulos de negócio distribuídos como pacotes Composer não editam arquivos do
 * core. Para rotas, cada pacote registra um callback aqui — no register() do seu
 * ServiceProvider, que roda antes do carregamento de routes/admin.php. O grupo
 * autenticado de routes/admin.php itera os callbacks e os executa dentro do
 * middleware admin (tenant, 2FA, inatividade), de modo que as rotas do módulo
 * herdam toda a stack sem duplicá-la.
 *
 * Permissões e itens de menu seguem outro caminho (merge em config('access.modules')
 * e config('admin-menu') no boot() do pacote), pois o core já consome essas configs
 * como fonte única de verdade.
 *
 * O estado é estático de propósito: um singleton de container dependeria da ordem
 * de registro entre o provider do core e os providers de pacote — e estes últimos
 * registram primeiro —, o que tornaria o binding frágil. Em testes, use flush()
 * para isolar cenários.
 */
final class ModuleRegistry
{
    /** @var list<Closure> */
    private static array $routeCallbacks = [];

    /**
     * Registra um callback que define rotas dentro do grupo autenticado /admin.
     * Deve ser chamado no register() do ServiceProvider do pacote.
     */
    public static function routes(Closure $callback): void
    {
        self::$routeCallbacks[] = $callback;
    }

    /**
     * Callbacks de rota acumulados, consumidos por routes/admin.php.
     *
     * @return list<Closure>
     */
    public static function routeCallbacks(): array
    {
        return self::$routeCallbacks;
    }

    /**
     * Limpa o estado acumulado. Útil em testes para isolar cenários.
     */
    public static function flush(): void
    {
        self::$routeCallbacks = [];
    }
}
