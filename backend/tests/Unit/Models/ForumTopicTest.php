<?php

namespace Tests\Unit\Models;

use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumTopicTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $topic = ForumTopic::create([
            'category_id' => ForumCategory::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'title' => 'Test Topic',
            'is_archived' => true,
        ]);

        $this->assertEquals('Test Topic', $topic->title);
        $this->assertTrue($topic->is_archived);
    }

    public function test_is_archived_cast_to_boolean(): void
    {
        $topic = ForumTopic::factory()->create(['is_archived' => 1]);

        $this->assertIsBool($topic->is_archived);
        $this->assertTrue($topic->is_archived);
    }

    public function test_belongs_to_category(): void
    {
        $category = ForumCategory::factory()->create();
        $topic = ForumTopic::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($topic->category->is($category));
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $topic = ForumTopic::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($topic->user->is($user));
    }

    public function test_has_many_posts(): void
    {
        $topic = ForumTopic::factory()->create();
        $post = ForumPost::factory()->create(['topic_id' => $topic->id]);

        $this->assertTrue($topic->posts->contains($post));
    }
}
