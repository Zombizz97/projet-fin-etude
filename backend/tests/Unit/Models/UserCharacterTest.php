<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Character;
use App\Models\UserCharacter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCharacterTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create();

        $uc = UserCharacter::create([
            'user_id' => $user->id,
            'character_id' => $character->id,
            'is_main' => true,
        ]);

        $this->assertTrue($uc->is_main);
        $this->assertEquals($user->id, $uc->user_id);
        $this->assertEquals($character->id, $uc->character_id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $uc = UserCharacter::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($uc->user->is($user));
    }

    public function test_belongs_to_character(): void
    {
        $character = Character::factory()->create();
        $uc = UserCharacter::factory()->create(['character_id' => $character->id]);

        $this->assertTrue($uc->character->is($character));
    }
}
