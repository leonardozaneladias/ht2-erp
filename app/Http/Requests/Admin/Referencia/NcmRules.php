<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Referencia;

use Illuminate\Validation\Rule;

/**
 * Regras de validação de NCM, reutilizadas pelo componente Livewire de
 * formulário (§5.2). codigo é a chave natural única (8 díg).
 */
final class NcmRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'codigo' => ['required', 'string', 'size:8', Rule::unique('ncms', 'codigo')->ignore($ignorarId)],
            'descricao' => ['required', 'string'],
        ];
    }
}
