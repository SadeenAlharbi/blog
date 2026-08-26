<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => User::ROLE_USER,
            'is_active' => true,
            'is_super_admin' => false,
        ];
    }

    /** An administrator promoted from an ordinary account. */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_ADMIN,
            'is_super_admin' => false,
        ]);
    }

    /** The platform owner — the only admin who may grant admin access. */
    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_ADMIN,
            'is_super_admin' => true,
        ]);
    }

    /** A deactivated account. */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}
