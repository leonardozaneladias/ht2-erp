<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Referencia;

use App\Enums\Referencia\RegiaoBrasil;
use Illuminate\Validation\Rule;

/**
 * Regras de validação de Estado (UF), reutilizadas pelo componente Livewire de
 * formulário (§5.2). codigo_ibge e sigla são chaves naturais únicas.
 */
final class EstadoRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'codigo_ibge' => ['required', 'string', 'size:2', Rule::unique('estados', 'codigo_ibge')->ignore($ignorarId)],
            'sigla' => ['required', 'string', 'size:2', Rule::unique('estados', 'sigla')->ignore($ignorarId)],
            'nome' => ['required', 'string', 'max:255'],
            'regiao' => ['required', Rule::enum(RegiaoBrasil::class)],
        ];
    }
}
