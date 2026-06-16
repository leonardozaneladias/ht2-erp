<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Referencia;

use Illuminate\Validation\Rule;

/**
 * Regras de validação de Banco (SPB), reutilizadas pelo componente Livewire de
 * formulário (§5.2). ispb é a chave natural única.
 */
final class BancoRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'ispb' => ['required', 'string', 'size:8', Rule::unique('bancos', 'ispb')->ignore($ignorarId)],
            'codigo_compe' => ['nullable', 'string', 'size:3'],
            'nome' => ['required', 'string', 'max:255'],
            'nome_completo' => ['nullable', 'string', 'max:255'],
            'ativo' => ['boolean'],
        ];
    }
}
