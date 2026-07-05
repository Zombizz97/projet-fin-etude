<?php

namespace Tests\Feature;

use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_characters(): void
    {
        Character::factory()->count(3)->create();

        $response = $this->getJson('/api/characters');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    public function test_index_returns_empty_array(): void
    {
        $response = $this->getJson('/api/characters');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    public function test_characters_ordered_by_name(): void
    {
        Character::factory()->create(['name' => 'Zelda']);
        Character::factory()->create(['name' => 'Mario']);

        $response = $this->getJson('/api/characters');
        $names = collect($response->json())->pluck('name')->toArray();

        $this->assertEquals(['Mario', 'Zelda'], $names);
    }
}
