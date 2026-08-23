<?php

declare(strict_types=1);

namespace HT2ML\Core\Exceptions\Referencia;

use RuntimeException;

/**
 * Lançada quando a importação de um CSV de dados de referência detecta corrupção:
 * arquivo ausente, cabeçalho divergente, nenhuma linha aproveitada de um arquivo
 * com dados, ou total abaixo do piso esperado. Faz o seed "falhar alto" em vez de gravar um
 * catálogo vazio/parcial reportando sucesso em verde.
 */
final class ImportacaoReferenciaException extends RuntimeException
{
    public static function arquivoAusente(string $tabela, string $caminho): self
    {
        return new self(sprintf(
            'Importação de %s abortada: CSV não encontrado em %s. '
            . 'Seeder de pacote precisa sobrescrever caminhoArquivo() para apontar aos próprios dados.',
            $tabela,
            $caminho,
        ));
    }

    /**
     * @param  list<string>  $esperado
     * @param  list<string>  $encontrado
     */
    public static function cabecalhoDivergente(string $tabela, string $arquivo, array $esperado, array $encontrado): self
    {
        return new self(sprintf(
            'Importação de %s abortada: o cabeçalho de %s diverge do esperado — colunas reordenadas/renomeadas? Esperado [%s], encontrado [%s].',
            $tabela,
            $arquivo,
            implode(', ', $esperado),
            implode(', ', $encontrado),
        ));
    }

    public static function nenhumaLinhaImportada(string $tabela, string $arquivo, int $lidas): self
    {
        return new self(sprintf(
            'Importação de %s abortada: %d linha(s) de dados lidas em %s, mas nenhuma pôde ser aproveitada (CSV malformado?).',
            $tabela,
            $arquivo,
            $lidas,
        ));
    }

    public static function abaixoDoMinimo(string $tabela, string $arquivo, int $inseridos, int $minimo): self
    {
        return new self(sprintf(
            'Importação de %s abortada: apenas %d linha(s) aproveitadas de %s, abaixo do mínimo esperado (%d) — CSV truncado?',
            $tabela,
            $arquivo,
            $inseridos,
            $minimo,
        ));
    }
}
