<?php

declare(strict_types=1);

namespace HT2ML\Core\Console\Commands;

use HT2ML\Core\Support\Generator\Extensao;
use HT2ML\Core\Support\Generator\ResolvedorDeStubs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Cria um MÓDULO — a área de negócio com superfície administrativa própria —
 * como pacote em packages/, e registra o path repository na raiz para
 * desenvolvimento local com symlink (ver ADR-0015 e ADR-0021).
 *
 *   php artisan make:modulo escola
 *   composer require ht2ml/extensao-escola:@dev
 *
 * Depois, gere cada recurso dentro dele com:
 *   php artisan make:recurso Aluno --modulo=escola --fields="..."
 *
 * Chamava-se `make:extensao` até 2026-08-28, e `make:modulo` era o gerador de
 * CRUD. A troca é o fim do quarto sentido simultâneo de "módulo" (ADR-0021):
 * hoje o módulo é a área de negócio, e "extensão" é só o envelope que a
 * distribui. Pacote continua sendo `{vendor}/extensao-{chave}` porque os cinco
 * publicados já usam esse nome — o envelope não mudou, o vocabulário mudou.
 *
 * A chave vai em kebab-case porque é ela que vira prefixo de permissão, key de
 * seção, namespace de view e prefixo de rota. Uma única forma, um único lugar.
 */
final class MakeModuloCommand extends Command
{
    protected $signature = 'make:modulo
        {chave : Chave do módulo em kebab-case (ex.: escola, fiscal-br)}
        {--force : Sobrescreve a casca se o pacote já existir}
        {--fields= : REMOVIDO em 2026-08-28 — pertence ao make:recurso}
        {--tenant : REMOVIDO em 2026-08-28 — pertence ao make:recurso}';

    protected $description = 'Cria um módulo como pacote em packages/ e registra o path repository.';

    /** @var list<string> */
    private array $criados = [];

    /** null = não tentou; false = Pint ausente ou falhou */
    private ?bool $formatado = null;

    public function handle(): int
    {
        $chave = trim((string) $this->argument('chave'));

        if ($chave === '') {
            $this->error('Informe uma chave de módulo válida (ex.: escola).');

            return self::FAILURE;
        }

        if (! $this->confirmarSentidoNovo($chave)) {
            return self::FAILURE;
        }

        $pkg = Extensao::paraNome($chave);
        $dirAbs = base_path($pkg->dir);

        if (File::isDirectory($dirAbs) && ! $this->option('force')) {
            $this->error("O pacote {$pkg->pacote} já existe em {$pkg->dir}. Use --force para sobrescrever.");

            return self::FAILURE;
        }

        // Stubs vêm do pacote, com o produto sobrescrevendo arquivo a arquivo.
        $stubs = new ResolvedorDeStubs('extensao');

        $repl = [
            '__MODULO_STUDLY__' => $pkg->studly,
            '__MODULO_SLUG__' => $pkg->slug,
            '__PKG_NAMESPACE__' => $pkg->namespaceBase,
            '__PROVIDER_CLASS__' => $pkg->providerClass,
            '__PKG_NAME__' => $pkg->pacote,
            '__VENDOR__' => (string) config('extensoes.vendor'),
        ];

        $arquivos = [
            'service-provider.stub' => "src/{$pkg->providerClass}.php",
            'config.stub' => "config/{$pkg->slug}.php",
            'routes.stub' => 'routes/admin.php',
            'readme.stub' => 'README.md',
            'gitignore.stub' => '.gitignore',
        ];

        foreach ($arquivos as $stub => $relativo) {
            $this->gerarArquivo($stubs->caminho($stub), "{$pkg->dir}/{$relativo}", $repl);
        }

        foreach (['database/migrations', 'database/factories', 'resources/views', 'tests'] as $vazio) {
            File::ensureDirectoryExists("{$dirAbs}/{$vazio}");
            File::put("{$dirAbs}/{$vazio}/.gitkeep", '');
        }

        $this->gerarComposerJson($pkg);
        $this->registrarPathRepository();

        $this->formatar();
        $this->resumo($pkg);

        return self::SUCCESS;
    }

    /**
     * Passa o Pint na casca recém-escrita.
     *
     * Pelo mesmo motivo do make:recurso: o stub sai com detalhes que o próprio
     * linter do projeto reprova, e o CI deste repositório roda `pint --test`.
     * Um módulo novo não pode nascer com o CI vermelho. Sem Pint instalado, a
     * instrução manual segue no resumo.
     */
    private function formatar(): void
    {
        $arquivos = array_values(array_filter(
            $this->criados,
            static fn (string $c): bool => str_ends_with($c, '.php'),
        ));

        if ($arquivos === []) {
            return;
        }

        $pint = base_path('vendor/bin/pint');

        if (! File::isFile($pint)) {
            return;
        }

        $this->formatado = Process::path(base_path())->run([$pint, ...$arquivos])->successful();
    }

    /**
     * `make:modulo` mudou de sentido em 2026-08-28: era o gerador de CRUD, hoje
     * cria o módulo. Um alias silencioso que faz outra coisa é pior que um erro
     * — quem digitasse a forma antiga ganharia um pacote inesperado no lugar de
     * dezenove arquivos. Então a forma antiga falha, ensinando o nome novo.
     *
     * O sinal é a grafia: a chave de um módulo é kebab-case, e o argumento
     * antigo era uma entidade em PascalCase singular (Funcionario). As duas
     * opções que só o gerador de CRUD tinha são o segundo sinal — e por isso
     * continuam declaradas na assinatura, marcadas REMOVIDO. Podem sair quando
     * a transição for história.
     */
    private function confirmarSentidoNovo(string $chave): bool
    {
        $legado = (string) $this->option('fields') !== '' || (bool) $this->option('tenant');

        if ($chave === Str::kebab($chave) && ! $legado) {
            return true;
        }

        $this->error('O comando `make:modulo` mudou de sentido em 2026-08-28.');
        $this->newLine();
        $this->line('Hoje ele cria um <options=bold>MÓDULO</> — a área de negócio distribuída como pacote —');
        $this->line('e a chave vai em kebab-case, porque ela vira prefixo de permissão, key de');
        $this->line('seção de menu, namespace de view e prefixo de rota:');
        $this->newLine();
        $this->line('  <fg=green>php artisan make:modulo ' . Str::kebab($chave) . '</>');
        $this->newLine();
        $this->line('Para gerar o CRUD de um <options=bold>RECURSO</> dentro de um módulo, o comando é outro:');
        $this->newLine();
        $this->line('  <fg=green>php artisan make:recurso ' . Str::studly(Str::singular($chave)) . ' --modulo=<chave> --fields="nome:string"</>');
        $this->newLine();
        $this->line('Ver ADR-0021 para o vocabulário.');

        return false;
    }

    /**
     * @param  array<string, string>  $repl
     */
    private function gerarArquivo(string $stubAbs, string $destinoRelativo, array $repl): void
    {
        $destino = base_path($destinoRelativo);
        $conteudo = strtr((string) File::get($stubAbs), $repl);

        File::ensureDirectoryExists(dirname($destino));
        File::put($destino, $conteudo);

        $this->criados[] = $destinoRelativo;
    }

    private function gerarComposerJson(Extensao $pkg): void
    {
        $ns = $pkg->namespaceBase . '\\';

        $composer = [
            'name' => $pkg->pacote,
            'description' => "Extensão {$pkg->studly}.",
            'type' => 'library',
            'license' => 'proprietary',
            // Declarado desde o nascimento: o provider gerado importa
            // ModuleRegistry, que vem de ht2ml/core. Extensão que não declara o
            // que usa não instala fora do monorepo — ver
            // docs/superficie-do-core.md.
            'require' => [
                'php' => '^8.4',
                'ht2ml/core' => '@dev',
                'illuminate/support' => '^13.0',
            ],
            'autoload' => [
                'psr-4' => [
                    $ns => 'src/',
                    $ns . 'Database\\Factories\\' => 'database/factories/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    $ns . 'Tests\\' => 'tests/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [$pkg->providerFqn()],
                ],
                // Fonte única da chave do módulo (ADR-0021). Daqui saem prefixo
                // de permissão, key de seção de menu, namespace de view e
                // prefixo de rota — e é contra ela que a coerência é conferida.
                // Fica em extra, e não em composer.json->type: `type` seleciona
                // installer, não documenta; sem plugin não faz nada, com plugin
                // muda o caminho de instalação.
                'ht2ml' => [
                    'tipo' => 'modulo',
                    'chave' => $pkg->slug,
                ],
            ],
            'minimum-stability' => 'stable',
        ];

        File::put(
            base_path("{$pkg->dir}/composer.json"),
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
        );

        $this->criados[] = "{$pkg->dir}/composer.json";
    }

    /**
     * Adiciona o path repository (packages/*) ao composer.json raiz, se ausente,
     * para o Composer resolver os pacotes locais via symlink.
     */
    private function registrarPathRepository(): void
    {
        $arquivo = base_path('composer.json');
        $original = (string) File::get($arquivo);

        /** @var array<string, mixed> $composer */
        $composer = (array) json_decode($original, true);

        /** @var list<array<string, mixed>> $repos */
        $repos = $composer['repositories'] ?? [];
        $url = (string) config('extensoes.path') . '/*';

        foreach ($repos as $repo) {
            if (($repo['type'] ?? null) === 'path' && ($repo['url'] ?? null) === $url) {
                return;
            }
        }

        $repos[] = ['type' => 'path', 'url' => $url, 'options' => ['symlink' => true]];
        $composer['repositories'] = array_values($repos);

        File::put($arquivo, $this->encodeJsonPreservandoIndentacao($composer, $original));

        $this->criados[] = 'composer.json (path repository)';
    }

    /**
     * json_encode usa 4 espaços fixos; aqui preservamos a indentação do arquivo
     * original (ex.: 2 espaços) para não gerar um diff de reformatação inteira.
     *
     * @param  array<string, mixed>  $dados
     */
    private function encodeJsonPreservandoIndentacao(array $dados, string $original): string
    {
        $json = (string) json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $indent = preg_match('/\n([ \t]+)"/', $original, $m) === 1 ? $m[1] : '    ';

        if ($indent !== '    ') {
            $json = (string) preg_replace_callback(
                '/^( +)/m',
                static fn (array $grupo): string => str_repeat($indent, intdiv(strlen($grupo[1]), 4)),
                $json,
            );
        }

        return $json . "\n";
    }

    private function resumo(Extensao $pkg): void
    {
        $this->newLine();
        $this->info("Módulo {$pkg->slug} criado no pacote {$pkg->pacote}.");

        foreach ($this->criados as $c) {
            $this->line("  <fg=green>criado</>  {$c}");
        }

        $this->newLine();
        $this->line('<options=bold>Próximos passos:</>');

        if ($this->formatado === true) {
            $this->line('  <fg=green>Pint já passou na casca gerada.</>');
        }

        $this->line("  1. composer require {$pkg->pacote}:@dev");
        $this->line("  2. php artisan make:recurso Recurso --modulo={$pkg->slug} --fields=\"...\"   (gera o CRUD no módulo)");
        $this->line('  3. php artisan migrate && php artisan access:sync && php artisan cache:clear');
        $this->newLine();
    }
}
