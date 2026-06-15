<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StatusExemplo;
use App\Models\Exemplo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exemplo>
 */
final class ExemploFactory extends Factory
{
    protected $model = Exemplo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => app(\App\Support\Tenancy\TenantContext::class)->empresaAtivaId() ?? \App\Models\Empresa::factory(),
            // Filial é opcional; os testes que exercitam a dimensão filial a definem
            // explicitamente (deve pertencer à mesma empresa do registro).
            'filial_id' => null,
            'nome' => fake()->words(2, true),
            'slug' => fake()->words(2, true),
            'site' => fake()->url(),
            'descricao' => fake()->sentence(),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->numerify('(##) #####-####'),
            'cep' => fake()->numerify('#####-###'),
            'cnpj' => fake()->numerify('##.###.###/####-##'),
            'cpf' => fake()->numerify('###.###.###-##'),
            'preco' => fake()->numberBetween(1000, 500000),
            'custo' => fake()->randomFloat(2, 1, 1000),
            'quantidade' => fake()->numberBetween(1, 1000),
            'cor' => fake()->hexColor(),
            'categoria' => fake()->randomElement(['servico', 'produto', 'assinatura']),
            'tags' => fake()->randomElements(['vip', 'novo', 'promo'], 2),
            'destaque' => fake()->boolean(),
            'data_inicio' => fake()->dateTimeBetween('-1 year'),
            'publicado_em' => fake()->dateTimeBetween('-1 year'),
            'status' => fake()->randomElement(StatusExemplo::cases()),
        ];
    }

    /**
     * Estado "na lixeira" (soft-deleted) para exercitar o fluxo de restauração.
     */
    public function trashed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_at' => now()->subDay(),
        ]);
    }
}
