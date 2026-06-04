<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
final class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->company(),
            'razao_social' => fake()->company() . ' Ltda',
            'cnpj' => fake()->numerify('##.###.###/0001-##'),
            'cidade' => fake()->city(),
            'estado' => fake()->randomElement(['SP', 'RJ', 'MG', 'PR', 'RS']),
            'ativo' => true,
        ];
    }
}
