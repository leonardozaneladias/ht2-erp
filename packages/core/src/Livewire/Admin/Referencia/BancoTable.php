<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Grid\Campo;
use HT2ML\Core\Livewire\Grid\RecursoTable;
use HT2ML\Core\Models\Referencia\Banco;

final class BancoTable extends RecursoTable
{
    protected function model(): string
    {
        return Banco::class;
    }

    protected function recurso(): string
    {
        return 'bancos';
    }

    protected function rotaBase(): string
    {
        return 'admin.referencia.bancos';
    }

    /**
     * @return list<Campo>
     */
    protected function campos(): array
    {
        return [
            Campo::texto('ispb', 'ISPB'),
            Campo::texto('codigo_compe', 'COMPE'),
            Campo::texto('nome', 'Nome'),
            Campo::texto('nome_completo', 'Nome completo'),
            Campo::booleano('ativo', 'Ativo'),
        ];
    }
}
