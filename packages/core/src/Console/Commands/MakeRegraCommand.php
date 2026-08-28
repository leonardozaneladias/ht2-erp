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
 * Gera uma regra de validação reutilizável, no produto ou dentro de um módulo.
 *
 *   php artisan make:regra MatriculaValida --modulo=escola
 *
 * O `make:rule` nativo do Laravel só sabe escrever em `app/Rules/`: não conhece
 * pacote, não conhece módulo, e num produto que instala a plataforma por
 * Composer a regra de domínio precisa nascer ao lado do domínio.
 *
 * O mecanismo já existia dos dois lados e só faltava a costura: `HT2ML\Core\
 * Rules\{Cpf,Cnpj,Pis,TituloEleitor}` são regras reutilizáveis para UMA
 * condição, e `Campo->regra()` as aceita na declaração do campo. O que não cabe
 * aqui é validação CRUZADA — a que depende de mais de um campo —, que mora em
 * {Recurso}Rules::regras(), onde o conjunto está visível.
 */
final class MakeRegraCommand extends Command
{
    protected $signature = 'make:regra
        {nome : Nome da regra em PascalCase (ex.: MatriculaValida)}
        {--modulo= : Chave do módulo que recebe a regra, kebab-case (ex.: escola)}
        {--mensagem= : Mensagem de falha (default: derivada do nome)}
        {--force : Sobrescreve arquivos existentes}';

    protected $description = 'Gera uma regra de validação reutilizável (ValidationRule) e o teste dela.';

    /** @var list<string> */
    private array $criados = [];

    public function handle(): int
    {
        $nome = Str::studly((string) $this->argument('nome'));

        if ($nome === '') {
            $this->error('Informe um nome de regra válido (ex.: MatriculaValida).');

            return self::FAILURE;
        }

        $pacote = null;
        $modulo = (string) $this->option('modulo');

        if ($modulo !== '') {
            $pacote = Extensao::paraNome($modulo);

            if (! File::isDirectory(base_path($pacote->dir))) {
                $this->error("Módulo {$pacote->pacote} não encontrado em {$pacote->dir}.");
                $this->line("Crie a casca primeiro:  php artisan make:modulo {$pacote->slug}");

                return self::FAILURE;
            }
        }

        $mensagem = (string) ($this->option('mensagem') ?: $this->mensagemPadrao($nome));

        $repl = [
            '__NS_REGRAS__' => $pacote === null ? 'App\\Rules' : $pacote->namespaceBase . '\\Rules',
            '__REGRA_STUDLY__' => $nome,
            '__REGRA_MENSAGEM__' => $mensagem,
            // No teste a mensagem já vem com :attribute resolvido pelo validador.
            '__REGRA_MENSAGEM_ATTR__' => str_replace(':attribute', 'campo', $mensagem),
        ];

        $destinos = $pacote === null
            ? [
                'regra.stub' => "app/Rules/{$nome}.php",
                'regra-test.stub' => "tests/Unit/Rules/{$nome}Test.php",
            ]
            : [
                'regra.stub' => "{$pacote->dir}/src/Rules/{$nome}.php",
                'regra-test.stub' => "{$pacote->dir}/tests/Unit/Rules/{$nome}Test.php",
            ];

        $stubs = new ResolvedorDeStubs('modulo');

        foreach ($destinos as $stub => $destino) {
            if (! $this->gerar($stubs, $stub, $destino, $repl)) {
                return self::FAILURE;
            }
        }

        $this->formatar();
        $this->resumo($nome, $repl['__NS_REGRAS__']);

        return self::SUCCESS;
    }

    /**
     * Mensagem padrão: genérica e correta.
     *
     * Tentei derivá-la do nome da regra e o resultado era português ruim —
     * "MatriculaValida" virava "não passou na validação de matricula valida".
     * Entre uma frase derivada que sai torta e uma frase curta que sempre sai
     * certa, a segunda; quem quer a mensagem do domínio passa --mensagem.
     */
    private function mensagemPadrao(string $nome): string
    {
        return 'O :attribute não é válido.';
    }

    /** @param array<string, string> $repl */
    private function gerar(ResolvedorDeStubs $stubs, string $stub, string $destinoRelativo, array $repl): bool
    {
        $destino = base_path($destinoRelativo);

        if (File::exists($destino) && ! $this->option('force')) {
            $this->error("{$destinoRelativo} já existe. Use --force para sobrescrever.");

            return false;
        }

        File::ensureDirectoryExists(dirname($destino));
        File::put($destino, strtr($stubs->conteudo($stub), $repl));

        $this->criados[] = $destinoRelativo;

        return true;
    }

    private function formatar(): void
    {
        $pint = base_path('vendor/bin/pint');

        if ($this->criados === [] || ! File::isFile($pint)) {
            return;
        }

        Process::path(base_path())->run([$pint, ...$this->criados]);
    }

    private function resumo(string $nome, string $namespace): void
    {
        $this->newLine();
        $this->info("Regra {$nome} gerada.");

        foreach ($this->criados as $c) {
            $this->line("  <fg=green>criado</>  {$c}");
        }

        $this->newLine();
        $this->line('<options=bold>Próximos passos:</>');
        $this->line('  1. Implemente a condição. Até lá a regra RECUSA tudo, de propósito —');
        $this->line('     regra recém-gerada que aceita tudo passa despercebida.');
        $this->line('  2. Preencha os dois casos do teste e tire o ->todo().');
        $this->line('  3. Use na declaração do campo:');
        $this->line("       Campo::texto('campo', 'Rótulo')->regra(new \\{$namespace}\\{$nome}())");
        $this->newLine();
    }
}
