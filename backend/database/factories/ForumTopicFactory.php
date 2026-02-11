<?php

namespace Database\Factories;

use App\Models\ForumTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ForumTopic> */
class ForumTopicFactory extends Factory
{
    protected $model = ForumTopic::class;

    public function definition(): array
    {
        return [
            'category_id' => null,
            'user_id' => null,
            'title' => ucfirst(fake()->sentence(6)),
            'is_archived' => false,
        ];
    }
}
