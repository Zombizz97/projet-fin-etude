<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use App\Models\UserCharacter;
use App\Models\UserRelationship;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PlayerControllerTest extends TestCase
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

    public function test_index_returns_all_players_with_characters(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create();
        UserCharacter::factory()->create([
            'user_id' => $user->id,
            'character_id' => $character->id,
        ]);

        $response = $this->getJson('/api/players');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.characters.0.character.name', $character->name);
    }

    public function test_index_returns_empty_array(): void
    {
        $response = $this->getJson('/api/players');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    public function test_index_with_friendship_status(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

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

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token($user))
            ->getJson('/api/players');

        $response->assertStatus(200);

        $friendPlayer = collect($response->json())->firstWhere('id', $friend->id);
        $this->assertNotNull($friendPlayer);
        $this->assertEquals('friend', $friendPlayer['friendship_status']);

        $strangerPlayer = collect($response->json())->firstWhere('id', $stranger->id);
        $this->assertNotNull($strangerPlayer);
        $this->assertNull($strangerPlayer['friendship_status']);
    }

    public function test_index_without_auth_returns_no_status(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson('/api/players');

        $response->assertStatus(200);
        $player = $response->json()[0] ?? [];
        $this->assertArrayNotHasKey('friendship_status', $player);
    }
}
