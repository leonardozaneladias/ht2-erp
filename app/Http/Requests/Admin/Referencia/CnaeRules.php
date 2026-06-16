<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Referencia;

use Illuminate\Validation\Rule;

/**
 * Regras de validação de CNAE, reutilizadas pelo componente Livewire de
 * formulário (§5.2). codigo é a chave natural única.
 */
final class CnaeRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'codigo' => ['required', 'string', 'size:7', Rule::unique('cnaes', 'codigo')->ignore($ignorarId)],
            'descricao' => ['required', 'string', 'max:255'],
            'secao' => ['nullable', 'string', 'size:1'],
            'divisao' => ['nullable', 'string', 'size:2'],
            'grupo' => ['nullable', 'string', 'size:3'],
            'classe' => ['nullable', 'string', 'size:5'],
        ];
    }
}
