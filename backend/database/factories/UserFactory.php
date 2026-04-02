<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'role' => 'staff',
            'department' => fake()->randomElement(['HR', 'Finance', 'Operations', 'IT']),
            'phone' => fake()->phoneNumber(),
            'status' => 'active',
            'force_password_change' => false,
            'email_verified_at' => now(),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['role' => 'super_admin']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function manager(): static
    {
        return $this->state(fn () => ['role' => 'manager']);
    }

    public function staff(): static
    {
        return $this->state(fn () => ['role' => 'staff']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }

    public function forcePasswordChange(): static
    {
        return $this->state(fn () => ['force_password_change' => true]);
    }
}
