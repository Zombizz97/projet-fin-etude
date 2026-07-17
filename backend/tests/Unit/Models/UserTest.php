<?php

namespace Tests\Unit\Models;

use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\User;
use App\Models\UserCharacter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'password' => 'secret123',
            'skill_level' => 'intermédiaire',
        ]);

        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('intermédiaire', $user->skill_level);
    }

    public function test_password_is_hidden(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_has_many_characters(): void
    {
        $user = User::factory()->create();
        $character = UserCharacter::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->characters->contains($character));
    }

    public function test_has_many_topics(): void
    {
        $user = User::factory()->create();
        $topic = ForumTopic::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->topics->contains($topic));
    }

    public function test_has_many_posts(): void
    {
        $user = User::factory()->create();
        $post = ForumPost::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->posts->contains($post));
    }

    public function test_username_is_unique(): void
    {
        User::factory()->create(['username' => 'uniqueuser']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['username' => 'uniqueuser']);
    }
}
