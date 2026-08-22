<?php

declare(strict_types=1);

namespace HT2ML\Rh\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFuncionarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('rh.funcionarios.editar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('funcionario');

        return FuncionarioRules::regras(is_numeric($id) ? (int) $id : null);
    }
}
