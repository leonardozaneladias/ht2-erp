<?php

declare(strict_types=1);

namespace HT2ML\Core\Http\Requests\Admin\Referencia;

use Illuminate\Validation\Rule;

/**
 * Regras de validação de Tipo de logradouro, reutilizadas pelo componente
 * Livewire de formulário (§5.2). nome é chave natural única.
 */
final class TipoLogradouroRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'nome' => ['required', 'string', 'max:60', Rule::unique('tipos_logradouro', 'nome')->ignore($ignorarId)],
            'codigo' => ['nullable', 'string', 'max:10'],
            'abrev' => ['nullable', 'string', 'max:15'],
            'ativo' => ['boolean'],
        ];
    }
}
