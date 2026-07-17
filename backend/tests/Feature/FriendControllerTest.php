<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRelationship;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FriendControllerTest extends TestCase
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

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$this->token($user)];
    }

    public function test_send_request_success(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$target->id);

        $response->assertStatus(201)
            ->assertJson(['message' => "Demande d'ami envoyée"]);

        $this->assertDatabaseHas('user_relationships', [
            'user_id' => $user->id,
            'related_user_id' => $target->id,
            'type' => 'pending',
        ]);
    }

    public function test_send_request_self(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$user->id);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Vous ne pouvez pas vous ajouter vous-même']);
    }

    public function test_send_request_nonexistent(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/99999');

        $response->assertStatus(404);
    }

    public function test_send_request_duplicate(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        UserRelationship::create([
            'user_id' => $user->id,
            'related_user_id' => $target->id,
            'type' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$target->id);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Demande déjà envoyée']);
    }

    public function test_send_request_already_friend(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        UserRelationship::create([
            'user_id' => $user->id,
            'related_user_id' => $target->id,
            'type' => 'friend',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$target->id);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Vous êtes déjà amis']);
    }

    public function test_send_request_blocked(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        UserRelationship::create([
            'user_id' => $user->id,
            'related_user_id' => $target->id,
            'type' => 'blocked',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$target->id);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Cet utilisateur est bloqué']);
    }

    public function test_accept_request(): void
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();
        UserRelationship::create([
            'user_id' => $requester->id,
            'related_user_id' => $user->id,
            'type' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$requester->id.'/accept');

        $response->assertStatus(200)
            ->assertJson(['message' => "Demande d'ami acceptée"]);

        $this->assertDatabaseHas('user_relationships', [
            'user_id' => $requester->id,
            'related_user_id' => $user->id,
            'type' => 'friend',
        ]);
        $this->assertDatabaseHas('user_relationships', [
            'user_id' => $user->id,
            'related_user_id' => $requester->id,
            'type' => 'friend',
        ]);
    }

    public function test_accept_no_pending(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$other->id.'/accept');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Aucune demande en attente']);
    }

    public function test_decline_request(): void
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();
        UserRelationship::create([
            'user_id' => $requester->id,
            'related_user_id' => $user->id,
            'type' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->deleteJson('/api/friends/'.$requester->id.'/accept');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Demande refusée']);

        $this->assertDatabaseMissing('user_relationships', [
            'user_id' => $requester->id,
            'related_user_id' => $user->id,
        ]);
    }

    public function test_decline_no_pending(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->deleteJson('/api/friends/'.$other->id.'/accept');

        $response->assertStatus(404);
    }

    public function test_index_returns_friends(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        UserRelationship::create([
            'user_id' => $user->id,
            'related_user_id' => $friend->id,
            'type' => 'friend',
        ]);
        UserRelationship::create([
            'user_id' => $friend->id,
            'related_user_id' => $user->id,
            'type' => 'friend',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/friends');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.username', $friend->username);
    }

    public function test_index_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/friends');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    public function test_pending_returns_requests(): void
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();
        UserRelationship::create([
            'user_id' => $requester->id,
            'related_user_id' => $user->id,
            'type' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/friends/pending');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.username', $requester->username);
    }

    public function test_sent_returns_sent_requests(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        UserRelationship::create([
            'user_id' => $user->id,
            'related_user_id' => $target->id,
            'type' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/friends/sent');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.username', $target->username);
    }

    public function test_blocked_returns_blocked_users(): void
    {
        $user = User::factory()->create();
        $blocked = User::factory()->create();
        UserRelationship::create([
            'user_id' => $user->id,
            'related_user_id' => $blocked->id,
            'type' => 'blocked',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson('/api/friends/blocked');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.username', $blocked->username);
    }

    public function test_remove_friend(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        UserRelationship::create(['user_id' => $user->id, 'related_user_id' => $friend->id, 'type' => 'friend']);
        UserRelationship::create(['user_id' => $friend->id, 'related_user_id' => $user->id, 'type' => 'friend']);

        $response = $this->withHeaders($this->authHeaders($user))
            ->deleteJson('/api/friends/'.$friend->id);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Ami supprimé']);

        $this->assertDatabaseMissing('user_relationships', [
            'user_id' => $user->id,
            'related_user_id' => $friend->id,
        ]);
        $this->assertDatabaseMissing('user_relationships', [
            'user_id' => $friend->id,
            'related_user_id' => $user->id,
        ]);
    }

    public function test_block_user(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$target->id.'/block');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Utilisateur bloqué']);

        $this->assertDatabaseHas('user_relationships', [
            'user_id' => $user->id,
            'related_user_id' => $target->id,
            'type' => 'blocked',
        ]);
    }

    public function test_block_self(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$user->id.'/block');

        $response->assertStatus(400)
            ->assertJson(['message' => 'Vous ne pouvez pas vous bloquer vous-même']);
    }

    public function test_block_nonexistent(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/99999/block');

        $response->assertStatus(404);
    }

    public function test_block_removes_existing_relationship(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        UserRelationship::create(['user_id' => $user->id, 'related_user_id' => $friend->id, 'type' => 'friend']);
        UserRelationship::create(['user_id' => $friend->id, 'related_user_id' => $user->id, 'type' => 'friend']);

        $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$friend->id.'/block');

        $this->assertDatabaseHas('user_relationships', [
            'user_id' => $user->id,
            'related_user_id' => $friend->id,
            'type' => 'blocked',
        ]);
        $this->assertDatabaseMissing('user_relationships', [
            'user_id' => $friend->id,
            'related_user_id' => $user->id,
        ]);
    }

    public function test_unblock(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        UserRelationship::create([
            'user_id' => $user->id,
            'related_user_id' => $target->id,
            'type' => 'blocked',
        ]);

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$target->id.'/unblock');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Utilisateur débloqué']);

        $this->assertDatabaseMissing('user_relationships', [
            'user_id' => $user->id,
            'related_user_id' => $target->id,
        ]);
    }

    public function test_unblock_not_blocked(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/friends/'.$target->id.'/unblock');

        $response->assertStatus(404)
            ->assertJson(['message' => "Cet utilisateur n'est pas bloqué"]);
    }
}
