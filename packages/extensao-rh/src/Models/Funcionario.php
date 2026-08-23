<?php

declare(strict_types=1);

namespace HT2ML\Rh\Models;

use App\Models\Concerns\BelongsToEmpresa;
use HT2ML\Core\Models\Concerns\Auditavel;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use HT2ML\Rh\Database\Factories\FuncionarioFactory;
use HT2ML\Rh\Enums\StatusFuncionario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * O Larastan infere os tipos das colunas a partir das migrations e não enxerga
 * os casts — por isso `salario` precisa ser declarado aqui. Sem esta linha ele
 * é inferido como int, o que levou a um `(int) $registro->salario` que estourava
 * em runtime, onde o valor é um objeto Money (ADR-0014).
 *
 * @property StatusFuncionario $status
 * @property \App\Support\Money\Money $salario
 * @property \Illuminate\Support\Carbon $admissao
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Funcionario extends Model implements UsaSoftDeletes
{
    use Auditavel;
    use BelongsToEmpresa;

    /** @use HasFactory<\HT2ML\Rh\Database\Factories\FuncionarioFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'funcionarios';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'cpf',
        'cargo',
        'salario',
        'admissao',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusFuncionario::class,
            'salario' => \App\Casts\MoneyCast::class,
            'admissao' => 'date',
        ];
    }

    /** Models de pacote precisam apontar a factory (o resolver padrão assume App\). */
    protected static function newFactory(): FuncionarioFactory
    {
        return FuncionarioFactory::new();
    }
}
