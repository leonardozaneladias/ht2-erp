<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\Referencia\DadosReferenciaSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Popula/atualiza os dados de referência (catálogos globais) a partir dos CSVs
 * versionados em database/data/referencia/. Idempotente (upsert): rodar de novo
 * atualiza in-place, não duplica.
 *
 * É o passo de deploy em produção — que NÃO roda `db:seed` (instala via Setup
 * Wizard) — e deve seguir o `migrate --force`. Não acoplar ao Setup Wizard.
 */
final class ReferenciaSyncCommand extends Command
{
    protected $signature = 'referencia:sync
        {conjunto?* : Limita a conjuntos específicos (ex.: estados); vazio = todos}
        {--dry-run : Executa sem gravar (rollback ao final)}';

    protected $description = 'Sincroniza os dados de referência (CSV → catálogos globais), idempotente.';

    public function handle(): int
    {
        $conjuntos = $this->conjuntosSelecionados();

        if ($conjuntos === []) {
            $this->error('Nenhum conjunto reconhecido. Disponíveis: ' . implode(', ', array_keys(DadosReferenciaSeeder::CONJUNTOS)));

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY-RUN: nada será gravado (rollback ao final).');
            DB::beginTransaction();
        }

        try {
            foreach ($conjuntos as $classe) {
                $seeder = app($classe);
                $seeder->setContainer($this->getLaravel())->setCommand($this);

                // Run real: cada conjunto entra tudo-ou-nada — um abort do seeder
                // (CSV corrompido) reverte limpo, sem deixar catálogo parcial gravado.
                // Dry-run: roda dentro da transação externa, desfeita no finally.
                $dryRun ? $seeder->run() : DB::transaction(fn () => $seeder->run());
            }
        } finally {
            // Garante o rollback do dry-run mesmo que um seeder lance no meio do caminho.
            if ($dryRun) {
                DB::rollBack();
                $this->info('DRY-RUN concluído — rollback aplicado.');
            }
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        activity('referencia')->log('referencia:sync: ' . implode(', ', array_keys($conjuntos)));
        $this->info('Dados de referência sincronizados.');

        return self::SUCCESS;
    }

    /**
     * Conjuntos a sincronizar (filtrados pelos argumentos, ou todos).
     *
     * @return array<string, class-string<Seeder>>
     */
    private function conjuntosSelecionados(): array
    {
        /** @var list<string> $filtro */
        $filtro = array_map('strtolower', (array) $this->argument('conjunto'));

        if ($filtro === []) {
            return DadosReferenciaSeeder::CONJUNTOS;
        }

        return array_intersect_key(DadosReferenciaSeeder::CONJUNTOS, array_flip($filtro));
    }
}
