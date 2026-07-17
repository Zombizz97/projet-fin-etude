<?php

namespace Tests\Feature;

use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_categories_with_topics(): void
    {
        $category = ForumCategory::factory()->create();
        ForumTopic::factory()->count(2)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/forums');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.topics.0.id', $category->topics->first()->id);
    }

    public function test_index_returns_empty_array(): void
    {
        $response = $this->getJson('/api/forums');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    public function test_show_returns_topic_with_posts(): void
    {
        $user = User::factory()->create();
        $category = ForumCategory::factory()->create();
        $topic = ForumTopic::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);
        ForumPost::factory()->count(3)->create([
            'topic_id' => $topic->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/forums/{$topic->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $topic->id)
            ->assertJsonStructure(['category', 'user', 'posts', 'posts_count']);
    }

    public function test_show_returns_404_for_nonexistent_topic(): void
    {
        $response = $this->getJson('/api/forums/99999');
        $response->assertStatus(404);
    }

    public function test_posts_returns_paginated_results(): void
    {
        $topic = ForumTopic::factory()->create();
        ForumPost::factory()->count(15)->create([
            'topic_id' => $topic->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->getJson("/api/forums/{$topic->id}/posts?per_page=5&page=1");

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_posts_defaults_to_per_page_10(): void
    {
        $topic = ForumTopic::factory()->create();
        ForumPost::factory()->count(12)->create([
            'topic_id' => $topic->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->getJson("/api/forums/{$topic->id}/posts");

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_posts_caps_per_page_at_100(): void
    {
        $topic = ForumTopic::factory()->create();
        ForumPost::factory()->count(5)->create([
            'topic_id' => $topic->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->getJson("/api/forums/{$topic->id}/posts?per_page=200");

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_posts_returns_404_for_nonexistent_topic(): void
    {
        $response = $this->getJson('/api/forums/99999/posts');
        $response->assertStatus(404);
    }
}
