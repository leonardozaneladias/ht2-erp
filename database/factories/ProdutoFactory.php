<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StatusProduto;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produto>
 */
final class ProdutoFactory extends Factory
{
    protected $model = Produto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => app(\App\Support\Tenancy\TenantContext::class)->empresaAtivaId() ?? \App\Models\Empresa::factory(),
            'nome' => fake()->words(2, true),
            'sku' => fake()->words(2, true),
            'preco' => fake()->numberBetween(1000, 500000),
            'descricao' => fake()->sentence(),
            'status' => fake()->randomElement(StatusProduto::cases()),
        ];
    }
}
