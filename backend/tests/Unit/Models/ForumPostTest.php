<?php

namespace Tests\Unit\Models;

use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $post = ForumPost::create([
            'topic_id' => ForumTopic::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'content' => 'Hello world',
        ]);

        $this->assertEquals('Hello world', $post->content);
    }

    public function test_belongs_to_topic(): void
    {
        $topic = ForumTopic::factory()->create();
        $post = ForumPost::factory()->create(['topic_id' => $topic->id]);

        $this->assertTrue($post->topic->is($topic));
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $post = ForumPost::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($post->user->is($user));
    }
}
