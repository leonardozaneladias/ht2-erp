<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia\Support;

use App\Enums\Referencia\OrigemRegistro;
use App\Exceptions\Referencia\ImportacaoReferenciaException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SplFileObject;

/**
 * Base para seeders de dados de referência alimentados por CSV versionado.
 *
 * Lê o CSV em streaming (não carrega tudo na memória), mapeia cada linha via
 * mapearLinha() e grava em chunks com `upsert` (ON CONFLICT DO UPDATE nativo do
 * PostgreSQL) — idempotente: re-seed atualiza in-place, não duplica. `created_at`
 * é preservado no conflito; só `updated_at` (e as colunas de dado) é atualizado.
 *
 * Falha alto contra CSV corrompido: conta lidas/inseridas/puladas e aborta com
 * ImportacaoReferenciaException quando o cabeçalho declarado em cabecalhoEsperado()
 * diverge, quando um arquivo com dados não rende nenhuma linha, ou quando o total
 * fica abaixo de minimoEsperado(). Ambos os hooks são opcionais (defaults mantêm
 * o comportamento permissivo de antes).
 */
abstract class CsvReferenceSeeder extends Seeder
{
    /** Linhas de dados consideradas no último run (exclui cabeçalho e brancas). */
    public int $lidas = 0;

    /** Linhas válidas enviadas ao upsert no último run. */
    public int $inseridos = 0;

    /** Linhas de dados descartadas por mapearLinha() no último run (inválidas). */
    public int $pulados = 0;

    /** Linhas do CSV ignoradas por colidirem com registro cadastrado nesta instalação. */
    public int $protegidos = 0;

    protected int $chunkSize = 1000;

    protected string $separador = ',';

    protected bool $temCabecalho = true;

    public function run(): void
    {
        DB::connection()->disableQueryLog();

        $this->lidas = 0;
        $this->inseridos = 0;
        $this->pulados = 0;
        $this->protegidos = 0;

        $caminho = $this->caminhoArquivo();

        if (! is_file($caminho)) {
            $this->aviso("CSV ausente: {$this->arquivo()} — pulando {$this->tabela()}.");

            return;
        }

        $arquivo = new SplFileObject($caminho);
        $arquivo->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        // 3º arg (escape) vazio: padrão RFC-4180 (aspas duplicadas) e silencia a
        // deprecação do escape "\\" no PHP 8.4.
        $arquivo->setCsvControl($this->separador, '"', '');

        $agora = now();
        $atualiza = [...$this->colunasUpdate(), 'updated_at'];
        $temOrigem = Schema::hasColumn($this->tabela(), 'origem');
        $protegidas = $temOrigem ? $this->chavesCadastradasAqui() : [];
        $buffer = [];
        $linhaNum = 0;

        foreach ($arquivo as $linha) {
            if (! is_array($linha)) {
                continue;
            }

            $linhaNum++;

            $campos = array_map(static fn (mixed $v): string => is_string($v) ? trim($v) : '', $linha);

            if ($this->temCabecalho && $linhaNum === 1) {
                $this->validarCabecalho($campos);

                continue;
            }

            if (implode('', $campos) === '') {
                continue; // linha em branco — não conta como lida
            }

            $this->lidas++;

            $mapeado = $this->mapearLinha($campos);

            if ($mapeado === null) {
                $this->pulados++;

                continue;
            }

            if (isset($protegidas[$this->assinatura($mapeado)])) {
                // Linha cadastrada nesta instalação com a mesma chave natural.
                // O sync nunca sobrescreve o que o cliente criou — previsibilidade
                // vale mais que deixar o dado oficial vencer em silêncio.
                $this->protegidos++;

                continue;
            }

            $mapeado['created_at'] = $agora;
            $mapeado['updated_at'] = $agora;
            if ($temOrigem) {
                $mapeado['origem'] = OrigemRegistro::Sincronizado->value;
            }
            $buffer[] = $mapeado;
            $this->inseridos++;

            if (count($buffer) >= $this->chunkSize) {
                DB::table($this->tabela())->upsert($buffer, $this->chaveNatural(), $atualiza);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table($this->tabela())->upsert($buffer, $this->chaveNatural(), $atualiza);
        }

        $this->logBalanco();
        $this->verificarPiso();
    }

    /**
     * Cabeçalho esperado (1ª linha do CSV, na ordem das colunas). Default null =
     * não valida (pula a 1ª linha cega, como antes). Quando declarado, divergência
     * aborta — defende contra colunas reordenadas/renomeadas que gravariam dado
     * na coluna errada sem erro de banco.
     *
     * @return list<string>|null
     */
    protected function cabecalhoEsperado(): ?array
    {
        return null;
    }

    /**
     * Piso mínimo de linhas inseridas. Default 0 = sem piso. Declarado nos
     * catálogos de cardinalidade estável; abaixo do piso aborta (defende contra
     * CSV truncado).
     */
    protected function minimoEsperado(): int
    {
        return 0;
    }

    /** Caminho absoluto do CSV. Seam para testes apontarem a um fixture. */
    protected function caminhoArquivo(): string
    {
        return database_path('data/referencia/' . $this->arquivo());
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

    /**
     * A coluna `origem` é opcional: esta base também serve tabelas que não
     * separam as duas populações (fixtures de teste, catálogos de extensão que
     * não expõem CRUD). Sem a coluna, o comportamento é o de antes.
     *
     * Chaves naturais já ocupadas por registros cadastrados nesta instalação,
     * indexadas para lookup O(1).
     *
     * @return array<string, true>
     */
    private function chavesCadastradasAqui(): array
    {
        $colunas = $this->chaveNatural();

        $linhas = DB::table($this->tabela())
            ->where('origem', OrigemRegistro::Manual->value)
            ->get($colunas);

        $mapa = [];

        foreach ($linhas as $linha) {
            $mapa[$this->assinatura((array) $linha)] = true;
        }

        return $mapa;
    }

    /**
     * Assinatura da chave natural de uma linha, para comparação.
     *
     * @param  array<string, mixed>  $linha
     */
    private function assinatura(array $linha): string
    {
        $partes = array_map(
            static fn (string $coluna): string => (string) ($linha[$coluna] ?? ''),
            $this->chaveNatural(),
        );

        return implode("\0", $partes);
    }

    /**
     * Confere a 1ª linha contra cabecalhoEsperado() (se declarado) e aborta na
     * divergência. Comparação tolerante a caixa/espaços e a um BOM UTF-8 inicial.
     *
     * @param  list<string>  $campos
     */
    private function validarCabecalho(array $campos): void
    {
        $esperado = $this->cabecalhoEsperado();

        if ($esperado === null) {
            return;
        }

        if (isset($campos[0])) {
            $campos[0] = preg_replace('/^\x{FEFF}/u', '', $campos[0]) ?? $campos[0];
        }

        $normalizar = static fn (array $cols): array => array_map(
            static fn (string $c): string => mb_strtolower(trim($c)),
            array_values($cols),
        );

        if ($normalizar($campos) !== $normalizar($esperado)) {
            throw ImportacaoReferenciaException::cabecalhoDivergente(
                $this->tabela(),
                $this->arquivo(),
                $esperado,
                $campos,
            );
        }
    }

    /** Aborta se um arquivo com dados não rendeu nada, ou ficou abaixo do piso. */
    private function verificarPiso(): void
    {
        if ($this->lidas > 0 && $this->inseridos === 0) {
            throw ImportacaoReferenciaException::nenhumaLinhaImportada(
                $this->tabela(),
                $this->arquivo(),
                $this->lidas,
            );
        }

        $minimo = $this->minimoEsperado();

        if ($minimo > 0 && $this->inseridos < $minimo) {
            throw ImportacaoReferenciaException::abaixoDoMinimo(
                $this->tabela(),
                $this->arquivo(),
                $this->inseridos,
                $minimo,
            );
        }
    }

    /** Loga o balanço do run (info; warn quando houve linhas puladas). */
    private function logBalanco(): void
    {
        $msg = sprintf(
            '  %s: lidas %d / inseridas %d / puladas %d%s.',
            $this->tabela(),
            $this->lidas,
            $this->inseridos,
            $this->pulados,
            $this->protegidos > 0
                ? sprintf(' / %d preservadas (cadastro próprio)', $this->protegidos)
                : '',
        );

        $this->pulados > 0 || $this->protegidos > 0 ? $this->aviso($msg) : $this->info($msg);
    }

    /** Escreve em stdout do comando, se houver um anexado (null-safe p/ testes). */
    private function info(string $msg): void
    {
        if (isset($this->command)) {
            $this->command->info($msg);
        }
    }

    private function aviso(string $msg): void
    {
        if (isset($this->command)) {
            $this->command->warn($msg);
        }
    }
}
