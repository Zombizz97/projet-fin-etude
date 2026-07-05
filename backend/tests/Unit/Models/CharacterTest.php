<?php

namespace Tests\Unit\Models;

use App\Models\Character;
use App\Models\UserCharacter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $character = Character::create([
            'name' => 'Mario',
            'icon_path' => 'mario.png',
        ]);

        $this->assertEquals('Mario', $character->name);
        $this->assertEquals('mario.png', $character->icon_path);
    }

    public function test_has_many_user_characters(): void
    {
        $character = Character::factory()->create();
        $userCharacter = UserCharacter::factory()->create(['character_id' => $character->id]);

        $this->assertTrue($character->userCharacters->contains($userCharacter));
    }
}
