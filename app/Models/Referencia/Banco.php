<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use Illuminate\Database\Eloquent\Model;

/**
 * Banco (participante do SPB) — dado de referência global. Chave natural: ispb.
 *
 * @property int $id
 * @property string $ispb
 * @property string|null $codigo_compe
 * @property string $nome
 * @property string|null $nome_completo
 * @property bool $ativo
 */
class Banco extends Model
{
    protected $table = 'bancos';

    /** @var list<string> */
    protected $fillable = ['ispb', 'codigo_compe', 'nome', 'nome_completo', 'ativo'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
