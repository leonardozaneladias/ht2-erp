<?php

declare(strict_types=1);

namespace HT2ML\Rh\Livewire\Departamentos;

use HT2ML\Core\Livewire\Grid\Campo;
use HT2ML\Core\Livewire\Grid\RecursoMultiEmpresa;
use HT2ML\Core\Livewire\Grid\RecursoTable;
use HT2ML\Rh\Enums\StatusDepartamento;
use HT2ML\Rh\Models\Departamento;
use Illuminate\Contracts\View\View;

final class DepartamentoTable extends RecursoTable
{
    use RecursoMultiEmpresa;

    /**
     * Declarado, e não derivado de recurso(): a key tem o prefixo do módulo
     * ('rh.departamentos-table') e é a âncora dos eventos de confirmação e da
     * persistência de colunas por usuário. Derivá-la agora renomearia o que já
     * está gravado no navegador de quem usa o sistema.
     */
    public string $tableName = 'rh.departamentos-table';

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Departamento) {
            return null;
        }

        return view('rh::livewire.departamentos._acoes', ['row' => $row, 'verLixeira' => $this->verLixeira]);
    }

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        $setUp = parent::setUp();

        // O RH tem toolbar de lixeira própria (rótulos do módulo).
        $setUp[0]->includeViewOnTop('rh::livewire.departamentos._lixeira-toggle');

        return $setUp;
    }

    /**
     * @return class-string<Departamento>
     */
    protected function model(): string
    {
        return Departamento::class;
    }

    protected function recurso(): string
    {
        return 'departamentos';
    }

    protected function modulo(): string
    {
        return 'rh';
    }

    protected function rotaBase(): string
    {
        return 'admin.rh.departamentos';
    }

    /**
     * @return list<Campo>
     */
    protected function campos(): array
    {
        return [
            Campo::texto('nome', 'Nome')->obrigatorio(),
            Campo::texto('sigla', 'Sigla'),
            Campo::enum('status', 'Status', StatusDepartamento::class)->obrigatorio(),
        ];
    }

    protected function tituloDaExportacao(): string
    {
        return 'Departamentos';
    }
}
