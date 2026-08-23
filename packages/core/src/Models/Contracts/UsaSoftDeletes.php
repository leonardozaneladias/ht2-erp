<?php

declare(strict_types=1);

namespace HT2ML\Core\Models\Contracts;

/**
 * Contrato que marca um model usando o trait Illuminate\Database\Eloquent\SoftDeletes.
 *
 * Não adiciona comportamento — o trait já implementa estes métodos. A interface
 * só os torna visíveis ao type-system (PHPStan) quando o model é manipulado de
 * forma genérica, via class-string<Model&UsaSoftDeletes> (ver HT2ML\Core\Livewire\Concerns\ComLixeira).
 * Assinaturas sem tipo de retorno para casar com o trait do framework.
 */
interface UsaSoftDeletes
{
    // Sem tipo de retorno nativo para casar com o trait SoftDeletes do framework
    // (que também não os declara); o @return satisfaz o PHPStan.

    /** @return bool */
    public function restore();

    /** @return bool */
    public function trashed();
}
