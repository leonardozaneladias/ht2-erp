<?php

declare(strict_types=1);

namespace HT2ML\Core\Http\Requests\Admin\Referencia;

use Illuminate\Validation\Rule;

/**
 * Regras de validação de País, reutilizadas pelo componente Livewire de
 * formulário (§5.2). codigo_iso2 é a chave natural única.
 */
final class PaisRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'codigo_iso2' => ['required', 'string', 'size:2', Rule::unique('paises', 'codigo_iso2')->ignore($ignorarId)],
            'codigo_iso3' => ['nullable', 'string', 'size:3'],
            'codigo_numerico' => ['nullable', 'string', 'size:3'],
            'nome' => ['required', 'string', 'max:255'],
            'ativo' => ['boolean'],
        ];
    }
}
