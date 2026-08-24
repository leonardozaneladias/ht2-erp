<?php

declare(strict_types=1);

namespace HT2ML\ExemploDemo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExemploRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('exemplos.criar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ExemploRules::regras();
    }
}
