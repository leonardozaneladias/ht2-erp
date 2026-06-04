<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\Filial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Filial>
 */
final class FilialFactory extends Factory
{
    protected $model = Filial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nome' => fake()->randomElement(['Matriz', 'Filial ' . fake()->city()]),
            'cidade' => fake()->city(),
            'estado' => fake()->randomElement(['SP', 'RJ', 'MG', 'PR', 'RS']),
            'e_matriz' => false,
            'ativo' => true,
        ];
    }
}
