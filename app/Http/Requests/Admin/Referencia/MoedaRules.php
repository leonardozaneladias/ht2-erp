<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Referencia;

use Illuminate\Validation\Rule;

/**
 * Regras de validação de Moeda (ISO 4217), reutilizadas pelo componente Livewire
 * de formulário (§5.2). codigo_iso é a chave natural única.
 */
final class MoedaRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'codigo_iso' => ['required', 'string', 'size:3', Rule::unique('moedas', 'codigo_iso')->ignore($ignorarId)],
            'numerico' => ['nullable', 'string', 'size:3'],
            'nome' => ['required', 'string', 'max:255'],
            'simbolo' => ['nullable', 'string', 'max:10'],
            'casas_decimais' => ['required', 'integer', 'min:0', 'max:10'],
            'ativo' => ['boolean'],
        ];
    }
}
