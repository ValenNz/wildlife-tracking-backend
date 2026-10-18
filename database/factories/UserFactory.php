<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'full_name' => fake('id_ID')->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement([
                'konservasionis_lapangan',
                'peneliti_ekologi',
                'pengambil_kebijakan',
            ]),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'administrator_sistem',
        ]);
    }

    public function unverifiedPassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => Hash::make('rahasia123'),
        ]);
    }
}
