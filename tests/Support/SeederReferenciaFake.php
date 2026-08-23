<?php

declare(strict_types=1);

namespace Tests\Support;

use HT2ML\Core\Support\Referencia\CsvReferenceSeeder;

/**
 * Seeder CSV concreto, só para testes: aponta a um fixture e expõe os hooks de
 * cabeçalho/mínimo como parâmetros, exercitando a classe-base CsvReferenceSeeder
 * (contagem, idempotência e os aborts de "falha alto") de forma isolada.
 */
final class SeederReferenciaFake extends CsvReferenceSeeder
{
    /**
     * @param  list<string>|null  $cabecalho
     */
    public function __construct(
        private readonly string $caminho,
        private readonly ?array $cabecalho = null,
        private readonly int $minimo = 0,
        private readonly string $tabelaDestino = 'ref_fixture',
    ) {}

    protected function caminhoArquivo(): string
    {
        return $this->caminho;
    }

    protected function arquivo(): string
    {
        return basename($this->caminho);
    }

    protected function tabela(): string
    {
        return $this->tabelaDestino;
    }

    protected function chaveNatural(): array
    {
        return ['chave'];
    }

    protected function colunasUpdate(): array
    {
        return ['valor'];
    }

    protected function cabecalhoEsperado(): ?array
    {
        return $this->cabecalho;
    }

    protected function minimoEsperado(): int
    {
        return $this->minimo;
    }

    protected function mapearLinha(array $linha): ?array
    {
        $chave = $linha[0] ?? '';

        if ($chave === '') {
            return null; // inválida: sem chave natural
        }

        return [
            'chave' => $chave,
            'valor' => $linha[1] ?? '',
        ];
    }
}
