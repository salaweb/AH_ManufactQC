<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'username' => null,
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Admin/QC user, authenticated with email.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    /**
     * QC user, authenticated with email.
     */
    public function qc(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Qc,
        ]);
    }

    /**
     * Operari user, authenticated with username instead of email.
     */
    public function operari(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
            'username' => fake()->unique()->userName(),
            'role' => UserRole::Operari,
        ]);
    }

    /**
     * Production operator, not authenticated anywhere yet — only linked to defects.
     */
    public function operariProduccio(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
            'username' => fake()->unique()->userName(),
            'role' => UserRole::OperariProduccio,
        ]);
    }
}
