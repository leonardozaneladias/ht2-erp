<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Http\Requests;

use HT2ERP\Rh\Enums\StatusDepartamento;
use Illuminate\Validation\Rule;

/**
 * Regras de validação de Departamento reutilizadas pelos FormRequests
 * (Store/Update) e pelo componente Livewire de formulário (§5.2). Centralizar
 * aqui mantém a validação fora do controller/componente e pronta para API.
 */
final class DepartamentoRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'sigla' => ['required', 'string', 'max:255', Rule::unique('departamentos', 'sigla')->ignore($ignorarId)],
            'status' => ['required', Rule::enum(StatusDepartamento::class)],
        ];
    }
}
