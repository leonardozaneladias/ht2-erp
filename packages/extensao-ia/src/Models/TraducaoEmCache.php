<?php

declare(strict_types=1);

namespace HT2ML\Ia\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Uma tradução já paga, guardada pelo hash do texto original.
 *
 * **Deliberadamente sem escopo de empresa**: o índice único é hash + idioma de
 * destino, e o mesmo texto traduzido uma vez serve qualquer empresa — é o que
 * economiza a chamada. Um global scope por tenant quebraria esse
 * compartilhamento. `empresa_id` fica só como referência de quem disparou a
 * primeira tradução, sem FK nem scope.
 *
 * Sem Auditavel (whitelist em tests/Arch/PlataformaTest.php): é cache, não
 * dado de negócio, e pode ser apagado inteiro sem perda.
 *
 * @property string $texto_traduzido
 */
final class TraducaoEmCache extends Model
{
    protected $table = 'ia_traducoes_cache';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'empresa_id',
        'hash_conteudo',
        'idioma_origem',
        'idioma_destino',
        'texto_traduzido',
        'provedor',
    ];
}
