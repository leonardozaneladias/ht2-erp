<?php

declare(strict_types=1);

namespace HT2ML\Core\Models\Concerns;

use HT2ML\Core\Enums\Referencia\OrigemRegistro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo cujas linhas têm origem declarada: sincronizada ou cadastrada aqui.
 *
 * A distinção é o que permite ao `referencia:sync` e ao CRUD coexistirem sem
 * disputar a mesma linha — ver HT2ML\Core\Enums\Referencia\OrigemRegistro.
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 *
 * @phpstan-require-implements \HT2ML\Core\Models\Contracts\TemOrigemDeclarada
 */
trait TemOrigem
{
    /** Linha mantida pela fonte oficial — não deve ser editada nem excluída. */
    public function sincronizado(): bool
    {
        return $this->getOrigem() === OrigemRegistro::Sincronizado;
    }

    /** Linha criada nesta instalação — editável, e intocada pelo sync. */
    public function cadastradoAqui(): bool
    {
        return ! $this->sincronizado();
    }

    public function getOrigem(): OrigemRegistro
    {
        $origem = $this->getAttribute('origem');

        return $origem instanceof OrigemRegistro ? $origem : OrigemRegistro::Sincronizado;
    }

    /** @param  Builder<static>  $query */
    public function scopeSincronizados(Builder $query): void
    {
        $query->where('origem', OrigemRegistro::Sincronizado->value);
    }

    /** @param  Builder<static>  $query */
    public function scopeCadastradosAqui(Builder $query): void
    {
        $query->where('origem', OrigemRegistro::Manual->value);
    }

    /**
     * Toda criação por Eloquent nasce como cadastro próprio.
     *
     * O `referencia:sync` grava por `DB::table()->upsert()`, que não passa por
     * aqui e declara `origem` explicitamente — é essa diferença de caminho que
     * separa as duas populações sem ninguém precisar lembrar de marcar nada.
     */
    protected static function bootTemOrigem(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('origem') === null) {
                $model->setAttribute('origem', OrigemRegistro::Manual);
            }
        });
    }
}
