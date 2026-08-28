<?php

declare(strict_types=1);

namespace HT2ML\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Lápide de `make:extensao`, removido em 2026-08-28.
 *
 * O comando existia para criar o pacote de uma extensão; hoje quem faz isso é
 * `make:modulo`, porque o que se cria é um MÓDULO — a área de negócio — e
 * "extensão" passou a nomear só o envelope que a distribui (ADR-0021).
 *
 * Existe como lápide, e não como ausência, porque o Symfony responderia a um
 * `make:extensao` com "Command is not defined" e uma sugestão por semelhança de
 * nome que não chega em `make:modulo`. Quem digitar o nome antigo — e a forma
 * antiga está escrita em quatro documentos e em cinco READMEs de pacote — sai
 * daqui sabendo o nome novo. Pode ser apagado quando a transição for história.
 */
final class MakeExtensaoCommand extends Command
{
    protected $signature = 'make:extensao {nome? : (removido)}';

    protected $description = 'REMOVIDO em 2026-08-28 — use make:modulo.';

    public function handle(): int
    {
        $chave = Str::kebab(trim((string) $this->argument('nome')));

        $this->error('O comando `make:extensao` foi removido em 2026-08-28.');
        $this->newLine();
        $this->line('O que ele criava é um <options=bold>MÓDULO</> — a área de negócio com superfície');
        $this->line('administrativa própria —, distribuído como pacote. O comando agora é:');
        $this->newLine();
        $this->line('  <fg=green>php artisan make:modulo ' . ($chave !== '' ? $chave : 'escola') . '</>');
        $this->newLine();
        $this->line('Ver ADR-0021 para o vocabulário: módulo, recurso, área de acesso, seção de menu.');

        return self::FAILURE;
    }
}
