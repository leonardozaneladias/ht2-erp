<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia;

use Database\Seeders\Referencia\Support\CsvReferenceSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Municípios IBGE (~5.570). CSV: `codigo_ibge,nome`. O `estado_id` é resolvido
 * pelos 2 primeiros dígitos do código IBGE do município (= código do estado).
 * Depende de EstadoSeeder ter rodado antes.
 */
final class MunicipioSeeder extends CsvReferenceSeeder
{
    /** @var array<string, int>|null mapa codigo_ibge(2) do estado => estado_id */
    private ?array $mapaEstados = null;

    protected function arquivo(): string
    {
        return 'municipios.csv';
    }

    protected function tabela(): string
    {
        return 'municipios';
    }

    protected function chaveNatural(): array
    {
        return ['codigo_ibge'];
    }

    protected function colunasUpdate(): array
    {
        return ['nome', 'estado_id'];
    }

    protected function cabecalhoEsperado(): ?array
    {
        return ['codigo_ibge', 'nome'];
    }

    protected function minimoEsperado(): int
    {
        return 5560;
    }

    protected function mapearLinha(array $linha): ?array
    {
        $codigo = $linha[0] ?? '';
        $nome = $linha[1] ?? '';

        if (strlen($codigo) !== 7 || $nome === '') {
            return null;
        }

        $estadoId = $this->mapaEstados()[substr($codigo, 0, 2)] ?? null;

        if ($estadoId === null) {
            return null; // estado não semeado — pula
        }

        return [
            'codigo_ibge' => $codigo,
            'nome' => $nome,
            'estado_id' => $estadoId,
        ];
    }

    /** @return array<string, int> */
    private function mapaEstados(): array
    {
        if ($this->mapaEstados !== null) {
            return $this->mapaEstados;
        }

        $mapa = [];
        foreach (DB::table('estados')->get(['id', 'codigo_ibge']) as $estado) {
            $mapa[(string) $estado->codigo_ibge] = (int) $estado->id;
        }

        return $this->mapaEstados = $mapa;
    }
}
