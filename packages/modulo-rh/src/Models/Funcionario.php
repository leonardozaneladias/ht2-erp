<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Models;

use App\Models\Concerns\Auditavel;
use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Contracts\UsaSoftDeletes;
use HT2ERP\Rh\Database\Factories\FuncionarioFactory;
use HT2ERP\Rh\Enums\StatusFuncionario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property StatusFuncionario $status
 * @property \Illuminate\Support\Carbon $admissao
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Funcionario extends Model implements UsaSoftDeletes
{
    use Auditavel;
    use BelongsToEmpresa;

    /** @use HasFactory<\HT2ERP\Rh\Database\Factories\FuncionarioFactory> */
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
