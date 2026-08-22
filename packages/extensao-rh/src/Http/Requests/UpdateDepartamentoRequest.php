<?php

declare(strict_types=1);

namespace HT2ML\Rh\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('rh.departamentos.editar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('departamento');

        return DepartamentoRules::regras(is_numeric($id) ? (int) $id : null);
    }
}
