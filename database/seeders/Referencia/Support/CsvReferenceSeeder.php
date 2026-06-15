<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia\Support;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use SplFileObject;

/**
 * Base para seeders de dados de referência alimentados por CSV versionado.
 *
 * Lê o CSV em streaming (não carrega tudo na memória), mapeia cada linha via
 * mapearLinha() e grava em chunks com `upsert` (ON CONFLICT DO UPDATE nativo do
 * PostgreSQL) — idempotente: re-seed atualiza in-place, não duplica. `created_at`
 * é preservado no conflito; só `updated_at` (e as colunas de dado) é atualizado.
 */
abstract class CsvReferenceSeeder extends Seeder
{
    protected int $chunkSize = 1000;

    protected string $separador = ',';

    protected bool $temCabecalho = true;

    public function run(): void
    {
        DB::connection()->disableQueryLog();

        $caminho = database_path('data/referencia/' . $this->arquivo());

        if (! is_file($caminho)) {
            $this->command->warn("CSV ausente: {$this->arquivo()} — pulando {$this->tabela()}.");

            return;
        }

        $arquivo = new SplFileObject($caminho);
        $arquivo->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $arquivo->setCsvControl($this->separador);

        $agora = now();
        $atualiza = [...$this->colunasUpdate(), 'updated_at'];
        $buffer = [];
        $total = 0;
        $linhaNum = 0;

        foreach ($arquivo as $linha) {
            if (! is_array($linha)) {
                continue;
            }

            $linhaNum++;

            if ($this->temCabecalho && $linhaNum === 1) {
                continue;
            }

            $campos = array_map(static fn (mixed $v): string => is_string($v) ? trim($v) : '', $linha);

            if (implode('', $campos) === '') {
                continue; // linha em branco
            }

            $mapeado = $this->mapearLinha($campos);

            if ($mapeado === null) {
                continue;
            }

            $mapeado['created_at'] = $agora;
            $mapeado['updated_at'] = $agora;
            $buffer[] = $mapeado;

            if (count($buffer) >= $this->chunkSize) {
                DB::table($this->tabela())->upsert($buffer, $this->chaveNatural(), $atualiza);
                $total += count($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table($this->tabela())->upsert($buffer, $this->chaveNatural(), $atualiza);
            $total += count($buffer);
        }

        $this->command->info(sprintf('  %s: %d registros.', $this->tabela(), $total));
    }

    /** Nome do arquivo CSV em database/data/referencia/. */
    abstract protected function arquivo(): string;

    /** Tabela de destino. */
    abstract protected function tabela(): string;

    /**
     * Coluna(s) da chave natural (ON CONFLICT do upsert).
     *
     * @return list<string>
     */
    abstract protected function chaveNatural(): array;

    /**
     * Colunas de dado atualizadas no conflito (não inclua a chave natural nem
     * created_at; updated_at é acrescentado automaticamente).
     *
     * @return list<string>
     */
    abstract protected function colunasUpdate(): array;

    /**
     * Mapeia uma linha do CSV (campos já com trim) para os atributos da tabela,
     * ou null para pular a linha (inválida/cabeçalho extra).
     *
     * @param  list<string>  $linha
     * @return array<string, mixed>|null
     */
    abstract protected function mapearLinha(array $linha): ?array;
}
