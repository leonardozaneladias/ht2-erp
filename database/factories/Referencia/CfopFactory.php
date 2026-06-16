<?php

declare(strict_types=1);

namespace Database\Factories\Referencia;

use App\Enums\Referencia\TipoCfop;
use App\Models\Referencia\Cfop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cfop>
 */
final class CfopFactory extends Factory
{
    protected $model = Cfop::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 1º dígito define o tipo (1/2/3 = entrada, 5/6/7 = saída); o restante é
        // único para respeitar o índice unique de codigo.
        $primeiro = fake()->randomElement(['1', '2', '3', '5', '6', '7']);
        $codigo = $primeiro . str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT);

        return [
            'codigo' => $codigo,
            'descricao' => fake()->sentence(),
            'tipo' => TipoCfop::peloCodigo($codigo),
            'aplicacao' => fake()->sentence(),
        ];
    }
}
