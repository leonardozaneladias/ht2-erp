<?php

declare(strict_types=1);

namespace HT2ML\Rh\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('rh.departamentos.criar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return DepartamentoRules::regras();
    }
}
