<?php

declare(strict_types=1);

namespace HT2ML\Core\Database\Factories\Referencia;

use HT2ML\Core\Enums\Referencia\RegiaoBrasil;
use HT2ML\Core\Models\Referencia\Estado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estado>
 */
final class EstadoFactory extends Factory
{
    protected $model = Estado::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $codigo = (string) fake()->unique()->numberBetween(11, 53);

        return [
            'codigo_ibge' => $codigo,
            'sigla' => mb_strtoupper(fake()->unique()->lexify('??')),
            'nome' => fake()->unique()->city(),
            'regiao' => RegiaoBrasil::peloCodigoIbge($codigo),
        ];
    }
}
