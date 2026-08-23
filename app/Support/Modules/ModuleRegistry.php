<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Enums\ModuloAcesso;
use Closure;
use InvalidArgumentException;

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
 * Permissões e itens de menu também passam por aqui. Antes cada extensão copiava
 * do stub o código que mesclava em config('access.modules') e config('admin-menu'),
 * o que espalhava a mesma lógica por todas elas — e foi por isso que o bug de dupla
 * aplicação sob `config:cache` precisou ser corrigido em dois lugares. Agora a
 * extensão apenas *declara* onde suas permissões e itens entram, e o core aplica
 * uma vez só, de forma idempotente, em AppServiceProvider::boot().
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

    /** @var array<string, array<string, array{label: string, descricao?: string}>> */
    private static array $permissoes = [];

    /** @var array<string, list<array<string, mixed>>> */
    private static array $itensDeMenu = [];

    /** @var array<string, class-string<\Illuminate\Database\Seeder>> */
    private static array $catalogos = [];

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
     * Declara as permissões da extensão dentro de um módulo do catálogo de acesso.
     *
     * O módulo precisa ser um caso existente de ModuloAcesso — extensões não criam
     * módulos novos. A referência fiscal, por exemplo, pertence a
     * ModuloAcesso::TabelasAuxiliares, não a Negocio.
     *
     * @param  array<string, array{label: string, descricao?: string}>  $permissoes
     */
    public static function permissoes(ModuloAcesso|string $modulo, array $permissoes): void
    {
        $chave = $modulo instanceof ModuloAcesso ? $modulo->value : $modulo;

        if (ModuloAcesso::tryFrom($chave) === null) {
            throw new InvalidArgumentException(
                "Módulo de acesso desconhecido: '{$chave}'. Use um caso de " . ModuloAcesso::class . '.',
            );
        }

        self::$permissoes[$chave] = [...(self::$permissoes[$chave] ?? []), ...$permissoes];
    }

    /**
     * Declara itens de menu da extensão dentro de uma seção existente da sidebar.
     *
     * @param  list<array<string, mixed>>  $itens
     */
    public static function itensDeMenu(string $secao, array $itens): void
    {
        // Deduplica por `key` no próprio registro. O registry é estático e a
        // declaração vive no boot() do provider, então cada nova instância da
        // aplicação no mesmo processo redeclara os mesmos itens — sem isto a
        // lista cresce sem parar. As permissões escapam por serem indexadas por
        // chave; o menu é lista e precisa do filtro explícito.
        $presentes = array_column(self::$itensDeMenu[$secao] ?? [], 'key');

        $novos = array_values(array_filter(
            $itens,
            static fn (array $item): bool => ! in_array($item['key'] ?? null, $presentes, true),
        ));

        self::$itensDeMenu[$secao] = [...(self::$itensDeMenu[$secao] ?? []), ...$novos];
    }

    /**
     * Itens de menu declarados para uma seção. Exposto para teste e diagnóstico.
     *
     * @return list<array<string, mixed>>
     */
    public static function aplicados(string $secao): array
    {
        return self::$itensDeMenu[$secao] ?? [];
    }

    /**
     * Aplica as contribuições declaradas ao config(). Chamado uma vez, no boot do
     * core, depois que todos os providers de extensão já registraram.
     *
     * Idempotente por construção: com `config:cache` a configuração é fotografada
     * já mesclada, e este método roda de novo sobre o próprio resultado.
     */
    public static function aplicarContribuicoes(): void
    {
        if (self::$permissoes !== []) {
            /** @var array<string, array<string, mixed>> $modulos */
            $modulos = (array) config('access.modules', []);

            foreach (self::$permissoes as $modulo => $permissoes) {
                // array_replace, não merge: chaves iguais são a MESMA permissão
                // reaplicada, e o merge recursivo transformaria 'label' => 'X'
                // em 'label' => ['X', 'X'].
                $modulos[$modulo] = array_replace($modulos[$modulo] ?? [], $permissoes);
            }

            config(['access.modules' => $modulos]);
        }

        if (self::$itensDeMenu === []) {
            return;
        }

        /** @var list<array<string, mixed>> $menu */
        $menu = (array) config('admin-menu', []);

        foreach ($menu as $i => $secao) {
            $chave = $secao['key'] ?? null;

            if (! is_string($chave) || ! isset(self::$itensDeMenu[$chave])) {
                continue;
            }

            /** @var list<array<string, mixed>> $existentes */
            $existentes = $secao['items'] ?? [];
            $presentes = array_column($existentes, 'key');

            $novos = array_values(array_filter(
                self::$itensDeMenu[$chave],
                static fn (array $item): bool => ! in_array($item['key'] ?? null, $presentes, true),
            ));

            $menu[$i]['items'] = [...$existentes, ...$novos];
        }

        config(['admin-menu' => $menu]);
    }

    /**
     * Declara um catálogo de referência mantido por CSV.
     *
     * Diferente de seeder(): além de semear, o catálogo entra na lista do
     * `referencia:sync`, que é o passo de deploy. Sem este canal, extrair um
     * catálogo do core o tiraria silenciosamente do comando de sincronização.
     *
     * @param  class-string<\Illuminate\Database\Seeder>  $seeder
     */
    public static function catalogoDeReferencia(string $slug, string $seeder): void
    {
        self::$catalogos[$slug] = $seeder;
    }

    /**
     * Catálogos de referência declarados por extensões (slug => seeder).
     *
     * @return array<string, class-string<\Illuminate\Database\Seeder>>
     */
    public static function catalogosDeReferencia(): array
    {
        return self::$catalogos;
    }

    /**
     * Limpa o estado acumulado. Útil em testes para isolar cenários.
     */
    public static function flush(): void
    {
        self::$routeCallbacks = [];
        self::$seeders = ['antes' => [], 'depois' => []];
        self::$permissoes = [];
        self::$itensDeMenu = [];
        self::$catalogos = [];
    }
}
