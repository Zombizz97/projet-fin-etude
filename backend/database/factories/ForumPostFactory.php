<?php

namespace Database\Factories;

use App\Models\ForumPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ForumPost> */
class ForumPostFactory extends Factory
{
    protected $model = ForumPost::class;

    public function definition(): array
    {
        return [
            'topic_id' => null,
            'user_id' => null,
            'content' => fake()->paragraphs(random_int(1, 2), true),
        ];
    }
}
