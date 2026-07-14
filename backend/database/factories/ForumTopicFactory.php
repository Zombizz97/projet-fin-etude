<?php

namespace Database\Factories;

use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ForumTopic> */
class ForumTopicFactory extends Factory
{
    protected $model = ForumTopic::class;

    public function definition(): array
    {
        return [
            'category_id' => ForumCategory::factory(),
            'user_id' => User::factory(),
            'title' => ucfirst(fake()->sentence(6)),
            'is_archived' => false,
        ];
    }
}
