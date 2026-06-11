<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminUser>
 */
final class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'ativo' => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(['ativo' => false]);
    }

    public function bloqueado(int $minutos = 30): static
    {
        return $this->state(['bloqueado_ate' => now()->addMinutes($minutos)]);
    }

    public function emailVerificado(): static
    {
        return $this->state(['email_verified_at' => now()]);
    }

    /**
     * 2FA confirmado com um segredo TOTP de teste (base32 válido).
     */
    public function comDoisFatores(string $secret = 'JBSWY3DPEHPK3PXP'): static
    {
        return $this->state([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
