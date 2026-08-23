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
 * Seeders seguem o mesmo desenho das rotas: cada extensão registra as classes
 * aqui no register(), e o DatabaseSeeder as executa. A ordem importa — os dados
 * de referência precisam existir antes dos seeders de demo do core, que os
 * consomem —, então o registro diz explicitamente se roda antes ou depois.
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

    /** @var array{antes: list<class-string<\Illuminate\Database\Seeder>>, depois: list<class-string<\Illuminate\Database\Seeder>>} */
    private static array $seeders = ['antes' => [], 'depois' => []];

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
     * Registra um seeder de extensão para ser executado pelo DatabaseSeeder.
     *
     * Deve ser chamado no register() do ServiceProvider da extensão.
     *
     * @param  class-string<\Illuminate\Database\Seeder>  $classe
     * @param  bool  $antesDoCore  true para dados que os seeders do core consomem
     *                             (catálogos de referência, por exemplo)
     */
    public static function seeder(string $classe, bool $antesDoCore = false): void
    {
        $balde = $antesDoCore ? 'antes' : 'depois';

        if (! in_array($classe, self::$seeders[$balde], true)) {
            self::$seeders[$balde][] = $classe;
        }
    }

    /**
     * Seeders de extensão acumulados, consumidos por DatabaseSeeder.
     *
     * @return list<class-string<\Illuminate\Database\Seeder>>
     */
    public static function seeders(bool $antesDoCore = false): array
    {
        return self::$seeders[$antesDoCore ? 'antes' : 'depois'];
    }

    /**
     * Limpa o estado acumulado. Útil em testes para isolar cenários.
     */
    public static function flush(): void
    {
        self::$routeCallbacks = [];
        self::$seeders = ['antes' => [], 'depois' => []];
    }
}
