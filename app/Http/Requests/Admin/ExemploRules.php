<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\StatusExemplo;
use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\Rule;

/**
 * Regras de validação de Exemplo reutilizadas pelos FormRequests
 * (Store/Update) e pelo componente Livewire de formulário (§5.2). Centralizar
 * aqui mantém a validação fora do controller/componente e pronta para API.
 */
final class ExemploRules
{
    /**
     * @return array<string, mixed>
     */
    public static function regras(?int $ignorarId = null): array
    {
        return [
            // Filial opcional, restrita às filiais da empresa ativa (tenant).
            'filial_id' => [
                'nullable',
                'integer',
                Rule::exists('filiais', 'id')->where('empresa_id', app(TenantContext::class)->empresaAtivaId()),
            ],
            'nome' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('exemplos', 'slug')->ignore($ignorarId)],
            'site' => ['nullable', 'url', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'email' => ['required', 'email:rfc', 'max:191'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'cep' => ['nullable', 'string', 'max:9'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'preco' => ['required', 'integer', 'min:0'],
            'custo' => ['required', 'numeric', 'min:0'],
            'quantidade' => ['required', 'integer'],
            'cor' => ['nullable', 'string', 'max:9'],
            'categoria' => ['required', Rule::in(['servico', 'produto', 'assinatura'])],
            'tags' => ['nullable', 'array'],
            'destaque' => ['boolean'],
            'data_inicio' => ['required', 'date'],
            'publicado_em' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(StatusExemplo::class)],
        ];
    }
}
