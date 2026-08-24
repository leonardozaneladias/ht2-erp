<?php

declare(strict_types=1);

namespace Database\Factories\Referencia;

use HT2ML\Core\Models\Referencia\TipoLogradouro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoLogradouro>
 */
final class TipoLogradouroFactory extends Factory
{
    protected $model = TipoLogradouro::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nome = fake()->unique()->randomElement(['Rua', 'Avenida', 'Travessa', 'Alameda', 'Praça', 'Rodovia', 'Estrada', 'Largo', 'Viela', 'Beco']);

        return [
            'nome' => $nome,
            'codigo' => (string) fake()->unique()->numberBetween(1, 999),
            'abrev' => mb_substr($nome, 0, 3),
            'ativo' => true,
        ];
    }
}
