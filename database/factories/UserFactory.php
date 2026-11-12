<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_MEMBER,
            'remember_token' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => User::ROLE_ADMIN]);
    }
}
