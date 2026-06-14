<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\StatusProduto;
use Illuminate\Validation\Rule;

/**
 * Regras de validação de Produto reutilizadas pelos FormRequests
 * (Store/Update) e pelo componente Livewire de formulário (§5.2). Centralizar
 * aqui mantém a validação fora do controller/componente e pronta para API.
 */
final class ProdutoRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('produtos', 'sku')->ignore($ignorarId)],
            'preco' => ['required', 'integer', 'min:0'],
            'descricao' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(StatusProduto::class)],
        ];
    }
}
