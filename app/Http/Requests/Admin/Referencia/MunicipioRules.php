<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Referencia;

use Illuminate\Validation\Rule;

/**
 * Regras de validação de Município IBGE, reutilizadas pelo componente Livewire de
 * formulário (§5.2). codigo_ibge é chave natural única; estado_id é FK obrigatória.
 */
final class MunicipioRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'codigo_ibge' => ['required', 'string', 'size:7', Rule::unique('municipios', 'codigo_ibge')->ignore($ignorarId)],
            'nome' => ['required', 'string', 'max:255'],
            'estado_id' => ['required', 'integer', Rule::exists('estados', 'id')],
        ];
    }
}
