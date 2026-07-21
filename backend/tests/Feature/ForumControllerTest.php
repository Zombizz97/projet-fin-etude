<?php

namespace Tests\Feature;

use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumPostVote;
use App\Models\ForumTopic;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ForumControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.jwt.secret', 'test-secret-key-12345-for-hs256-must-be-32bytes');
    }

    private function token(User $user): string
    {
        return JWT::encode([
            'iss' => url('/'),
            'sub' => $user->id,
            'iat' => now()->timestamp,
            'exp' => now()->addDays(7)->timestamp,
        ], Config::get('services.jwt.secret'), 'HS256');
    }

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

    public function test_store_creates_topic_and_post(): void
    {
        $user = User::factory()->create();
        $category = ForumCategory::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->postJson('/api/forums', [
                'category_id' => $category->id,
                'title' => 'New Topic',
                'content' => 'First post content',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'New Topic')
            ->assertJsonStructure(['id', 'title', 'category', 'user', 'posts_count']);

        $this->assertDatabaseHas('forum_topics', ['title' => 'New Topic']);
        $this->assertDatabaseHas('forum_posts', ['content' => 'First post content']);
    }

    public function test_store_unauthenticated(): void
    {
        $response = $this->postJson('/api/forums', [
            'category_id' => 1,
            'title' => 'Test',
            'content' => 'Content',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_validation_errors(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->postJson('/api/forums', [
                'category_id' => 999,
                'title' => '',
                'content' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_store_post_adds_reply(): void
    {
        $user = User::factory()->create();
        $topic = ForumTopic::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->postJson("/api/forums/{$topic->id}/posts", [
                'content' => 'Reply content',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('content', 'Reply content')
            ->assertJsonPath('user.username', $user->username);

        $this->assertDatabaseHas('forum_posts', [
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'content' => 'Reply content',
        ]);
    }

    public function test_store_post_unauthenticated(): void
    {
        $topic = ForumTopic::factory()->create();

        $response = $this->postJson("/api/forums/{$topic->id}/posts", [
            'content' => 'Reply',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_post_404(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->postJson('/api/forums/99999/posts', ['content' => 'test']);

        $response->assertStatus(404);
    }

    public function test_vote_up(): void
    {
        $user = User::factory()->create();
        $post = ForumPost::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->postJson("/api/posts/{$post->id}/vote", ['vote' => 'up']);

        $response->assertStatus(201)
            ->assertJsonPath('vote_balance', 1)
            ->assertJsonPath('user_vote', 'up');
    }

    public function test_vote_down(): void
    {
        $user = User::factory()->create();
        $post = ForumPost::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->postJson("/api/posts/{$post->id}/vote", ['vote' => 'down']);

        $response->assertStatus(201)
            ->assertJsonPath('vote_balance', -1)
            ->assertJsonPath('user_vote', 'down');
    }

    public function test_vote_toggle_removes_same_vote(): void
    {
        $user = User::factory()->create();
        $post = ForumPost::factory()->create();
        ForumPostVote::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'vote' => 'up',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->postJson("/api/posts/{$post->id}/vote", ['vote' => 'up']);

        $response->assertStatus(200)
            ->assertJsonPath('vote_balance', 0)
            ->assertJsonPath('user_vote', null);
    }

    public function test_vote_change(): void
    {
        $user = User::factory()->create();
        $post = ForumPost::factory()->create();
        ForumPostVote::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'vote' => 'up',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->postJson("/api/posts/{$post->id}/vote", ['vote' => 'down']);

        $response->assertStatus(200)
            ->assertJsonPath('vote_balance', -1)
            ->assertJsonPath('user_vote', 'down');
    }

    public function test_vote_unauthenticated(): void
    {
        $post = ForumPost::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/vote", ['vote' => 'up']);

        $response->assertStatus(401);
    }

    public function test_vote_invalid_type(): void
    {
        $user = User::factory()->create();
        $post = ForumPost::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->postJson("/api/posts/{$post->id}/vote", ['vote' => 'invalid']);

        $response->assertStatus(422);
    }

    public function test_vote_404(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->postJson('/api/posts/99999/vote', ['vote' => 'up']);

        $response->assertStatus(404);
    }
}
