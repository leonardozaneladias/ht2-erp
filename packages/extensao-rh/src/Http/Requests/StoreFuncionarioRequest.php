<?php

declare(strict_types=1);

namespace HT2ML\Rh\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuncionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('rh.funcionarios.criar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return FuncionarioRules::regras();
    }
}
