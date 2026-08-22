<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Generator\Extensao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Cria a casca de uma extensão em packages/ e registra o path
 * repository na raiz, para desenvolvimento local com symlink (ver ADR-0015).
 *
 *   php artisan make:extensao Rh
 *   composer require ht2ml/extensao-rh:@dev
 *
 * Depois, gere o CRUD dentro do pacote com:
 *   php artisan make:modulo Funcionario --module=Rh --fields="..."
 */
final class MakeExtensaoCommand extends Command
{
    protected $signature = 'make:extensao
        {nome : Nome da extensão no singular, PascalCase (ex.: Rh)}
        {--force : Sobrescreve a casca se a extensão já existir}';

    protected $description = 'Cria a casca de uma extensão em packages/ e registra o path repository.';

    /** @var list<string> */
    private array $criados = [];

    public function handle(): int
    {
        $nome = Str::studly((string) $this->argument('nome'));

        if ($nome === '') {
            $this->error('Informe um nome de extensão válido (ex.: Rh).');

            return self::FAILURE;
        }

        $pkg = Extensao::paraNome($nome);
        $dirAbs = base_path($pkg->dir);

        if (File::isDirectory($dirAbs) && ! $this->option('force')) {
            $this->error("O pacote {$pkg->pacote} já existe em {$pkg->dir}. Use --force para sobrescrever.");

            return self::FAILURE;
        }

        $stubDir = base_path('stubs/extensao');

        if (! File::isDirectory($stubDir)) {
            $this->error("Stubs não encontrados em {$stubDir}. Eles fazem parte do boilerplate.");

            return self::FAILURE;
        }

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
            $this->gerarArquivo("{$stubDir}/{$stub}", "{$pkg->dir}/{$relativo}", $repl);
        }

        foreach (['database/migrations', 'database/factories', 'resources/views', 'tests'] as $vazio) {
            File::ensureDirectoryExists("{$dirAbs}/{$vazio}");
            File::put("{$dirAbs}/{$vazio}/.gitkeep", '');
        }

        $this->gerarComposerJson($pkg);
        $this->registrarPathRepository();

        $this->resumo($pkg);

        return self::SUCCESS;
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
            'require' => [
                'php' => '^8.4',
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
        $this->info("Casca do pacote {$pkg->pacote} criada.");

        foreach ($this->criados as $c) {
            $this->line("  <fg=green>criado</>  {$c}");
        }

        $this->newLine();
        $this->line('<options=bold>Próximos passos:</>');
        $this->line("  1. composer require {$pkg->pacote}:@dev");
        $this->line("  2. php artisan make:modulo Recurso --module={$pkg->studly} --fields=\"...\"   (gera CRUD no pacote)");
        $this->line('  3. php artisan migrate && php artisan access:sync && php artisan cache:clear');
        $this->newLine();
    }
}
