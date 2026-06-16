<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Referencia;

use App\Enums\Referencia\TipoCfop;
use Illuminate\Validation\Rule;

/**
 * Regras de validação de CFOP, reutilizadas pelo componente Livewire de
 * formulário (§5.2). codigo é a chave natural única.
 */
final class CfopRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'codigo' => ['required', 'string', 'size:4', Rule::unique('cfops', 'codigo')->ignore($ignorarId)],
            'descricao' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::enum(TipoCfop::class)],
            'aplicacao' => ['nullable', 'string', 'max:255'],
        ];
    }
}
