<?php

declare(strict_types=1);

namespace HT2ML\Rh\Http\Requests;

use HT2ML\Rh\Enums\StatusFuncionario;
use Illuminate\Validation\Rule;

/**
 * Regras de validação de Funcionario reutilizadas pelos FormRequests
 * (Store/Update) e pelo componente Livewire de formulário (§5.2). Centralizar
 * aqui mantém a validação fora do controller/componente e pronta para API.
 */
final class FuncionarioRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['required', new \App\Rules\Cpf],
            'cargo' => ['required', 'string', 'max:255'],
            'salario' => ['required', 'integer', 'min:0'],
            'admissao' => ['required', 'date'],
            'status' => ['required', Rule::enum(StatusFuncionario::class)],
        ];
    }
}
