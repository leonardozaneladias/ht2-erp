<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\FilialDTO;
use App\Exceptions\FilialException;
use App\Models\Filial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AtualizarFilialAction
{
    public function execute(Filial $filial, FilialDTO $dto): Filial
    {
        if ($filial->e_matriz && ! $dto->ativo) {
            throw FilialException::matrizProtegida();
        }

        return DB::transaction(function () use ($filial, $dto): Filial {
            $filial->update($dto->paraModel());

            activity('empresas')
                ->performedOn($filial->empresa)
                ->causedBy(Auth::guard('admin')->user())
                ->event('filial-atualizada')
                ->withProperties(['filial_id' => $filial->id, 'nome' => $filial->nome, 'ativo' => $filial->ativo])
                ->log('Filial atualizada');

            return $filial->fresh() ?? $filial;
        });
    }
}
