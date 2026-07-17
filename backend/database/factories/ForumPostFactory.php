<?php

namespace Database\Factories;

use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ForumPost> */
class ForumPostFactory extends Factory
{
    protected $model = ForumPost::class;

    public function definition(): array
    {
        return [
            'topic_id' => ForumTopic::factory(),
            'user_id' => User::factory(),
            'content' => fake()->paragraphs(random_int(1, 2), true),
        ];
    }
}
