<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $levels = ['débutant','intermédiaire','confirmé','professionnel'];
        return [
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password', // sera hashé via cast
            'skill_level' => fake()->randomElement($levels),
        ];
    }
}

