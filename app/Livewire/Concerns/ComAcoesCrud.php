<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

/**
 * Menu de ações da linha (kebab) para um CRUD padrão: Ver, Editar, Excluir → lixeira,
 * Restaurar e Excluir definitivamente — cada item guardado pela Policy do model.
 *
 * Antes, cada CRUD tinha um `_acoes.blade.php` próprio. Eram 28 arquivos de ~40 linhas que,
 * uma vez normalizados, revelaram-se O MESMO ARQUIVO: variavam em três tokens — o docblock
 * do $row, o nome do evento que abre a ficha, e a rota de edição. Nada mais.
 *
 * Aqui a view é uma só (`livewire.admin.partials.grid-acoes`) e os três tokens viram dois
 * métodos declarados pelo componente. Não são deriváveis do `tableName`: o nome do
 * componente, o do evento e o da rota divergem com frequência (mais ainda em módulos-pacote,
 * onde o componente é prefixado pelo namespace do pacote e a rota não).
 *
 * CRUDs com ações EXTRAS (alternar status, personificar, imprimir) não usam este trait:
 * mantêm o seu `actionsFromView()`, agora sobre o componente `x-admin.crud-row-actions`,
 * que oferece as ações padrão e um slot para as suas.
 *
 * @mixin \PowerComponents\LivewirePowerGrid\PowerGridComponent
 */
trait ComAcoesCrud
{
    /** Ações padrão da linha. Os CRUDs com ações extras sobrescrevem este método. */
    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Model) {
            return null;
        }

        return view('livewire.admin.partials.grid-acoes', [
            'row' => $row,
            'verLixeira' => $this->verLixeira,
            'eventoVer' => $this->eventoVer(),
            'rotaEditar' => $this->rotaEditar($row),
        ]);
    }

    /**
     * Evento que abre a ficha "Ver" — namespaced por recurso para não colidir quando há
     * mais de um grid na página (ver ComFicha).
     */
    abstract protected function eventoVer(): string;

    /** URL de edição do registro. */
    abstract protected function rotaEditar(Model $row): string;
}
