<?php

declare(strict_types=1);

namespace HT2ML\ExemploDemo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExemploRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('exemplos.editar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('exemplo');

        return ExemploRules::regras(is_numeric($id) ? (int) $id : null);
    }
}
