<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Character;
use App\Models\UserCharacter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerControllerTest extends TestCase
{
    use RefreshDatabase;

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
}
