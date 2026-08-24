<?php

declare(strict_types=1);

namespace HT2ML\Documentos\Support;

use HT2ML\Documentos\Enums\TipoDocumento;
use HT2ML\Documentos\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

/**
 * Gera números sequenciais de documentos sem buracos nem duplicatas.
 *
 * Usa transação + lockForUpdate para garantir atomicidade em acessos concorrentes.
 * O número resultante é formatado conforme o template do tipo (ou um template
 * customizado), com suporte aos placeholders {ANO} e {SEQ:N}.
 */
final class GeradorNumeroDocumento
{
    /**
     * Gera e persiste o próximo número sequencial para a combinação
     * empresa/tipo/ano, retornando o número formatado conforme o template.
     *
     * @param  string|null  $template  Template customizado (ex.: "MAT-{ANO}-{SEQ:6}").
     *                                 Se null, usa o template padrão do TipoDocumento.
     */
    public function gerar(int $empresaId, TipoDocumento $tipo, int $ano, ?string $template = null): string
    {
        $proximoNumero = DB::transaction(function () use ($empresaId, $tipo, $ano): int {
            $sequencia = DocumentSequence::where('empresa_id', $empresaId)
                ->where('tipo', $tipo->value)
                ->where('ano', $ano)
                ->lockForUpdate()
                ->first();

            if ($sequencia === null) {
                $sequencia = DocumentSequence::create([
                    'empresa_id' => $empresaId,
                    'tipo' => $tipo->value,
                    'ano' => $ano,
                    'ultimo_numero' => 0,
                ]);
            }

            $sequencia->ultimo_numero += 1;
            $sequencia->save();

            return $sequencia->ultimo_numero;
        });

        return $this->formatar($tipo, $ano, $proximoNumero, $template);
    }

    /**
     * Formata o número aplicando os placeholders ao template.
     *
     * Placeholders suportados:
     *  - {ANO}   → ano com 4 dígitos
     *  - {SEQ:N} → sequencial zero-padded com N dígitos
     */
    private function formatar(TipoDocumento $tipo, int $ano, int $numero, ?string $template): string
    {
        $template ??= $tipo->template();

        $resultado = preg_replace_callback(
            '/\{SEQ:(\d+)\}/',
            static fn (array $matches): string => str_pad((string) $numero, (int) $matches[1], '0', STR_PAD_LEFT),
            str_replace('{ANO}', (string) $ano, $template),
        );

        return (string) $resultado;
    }
}
