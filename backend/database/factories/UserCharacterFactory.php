<?php

namespace Database\Factories;

use App\Models\UserCharacter;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserCharacter> */
class UserCharacterFactory extends Factory
{
    protected $model = UserCharacter::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'character_id' => null,
            'is_main' => fake()->boolean(30),
        ];
    }
}
