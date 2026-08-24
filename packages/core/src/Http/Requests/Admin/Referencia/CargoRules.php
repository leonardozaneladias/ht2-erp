<?php

declare(strict_types=1);

namespace HT2ML\Core\Http\Requests\Admin\Referencia;

use Illuminate\Validation\Rule;

/**
 * Regras de validação de Cargo (CBO), reutilizadas pelo componente Livewire de
 * formulário (§5.2). codigo_cbo é chave natural única.
 */
final class CargoRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'codigo_cbo' => ['required', 'string', 'size:6', Rule::unique('cargos', 'codigo_cbo')->ignore($ignorarId)],
            'descricao' => ['required', 'string', 'max:255'],
            'ativo' => ['boolean'],
        ];
    }
}
