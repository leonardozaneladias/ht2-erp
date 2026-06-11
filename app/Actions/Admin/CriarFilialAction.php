<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\FilialDTO;
use App\Models\Empresa;
use App\Models\Filial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CriarFilialAction
{
    public function execute(Empresa $empresa, FilialDTO $dto): Filial
    {
        return DB::transaction(function () use ($empresa, $dto): Filial {
            /** @var Filial $filial */
            $filial = $empresa->filiais()->create($dto->paraModel() + ['e_matriz' => false]);

            activity('empresas')
                ->performedOn($empresa)
                ->causedBy(Auth::guard('admin')->user())
                ->event('filial-criada')
                ->withProperties(['filial_id' => $filial->id, 'nome' => $filial->nome])
                ->log('Filial criada');

            return $filial;
        });
    }
}
