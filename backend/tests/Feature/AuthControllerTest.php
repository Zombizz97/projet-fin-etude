<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.jwt.secret', 'test-secret-key-12345-for-hs256-must-be-32bytes');
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => 'newuser',
            'password' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user' => ['id', 'username'], 'token']);

        $this->assertDatabaseHas('users', ['username' => 'newuser']);
    }

    public function test_register_with_character(): void
    {
        $character = Character::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'username' => 'player1',
            'password' => 'secret123',
            'character_id' => $character->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_characters', [
            'user_id' => $response->json('user.id'),
            'character_id' => $character->id,
            'is_main' => true,
        ]);
    }

    public function test_register_validation_errors(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => '',
            'password' => '12',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_duplicate_username(): void
    {
        User::factory()->create(['username' => 'existing']);

        $response = $this->postJson('/api/auth/register', [
            'username' => 'existing',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'loginuser',
            'password' => bcrypt('correctpassword'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'loginuser',
            'password' => 'correctpassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'nonexistent',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Identifiants invalides']);
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $this->generateToken($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.username', $user->username);
    }

    public function test_me_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }

    public function test_update_profile(): void
    {
        $user = User::factory()->create(['username' => 'oldname']);
        $token = $this->generateToken($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/user', [
                'username' => 'newname',
                'skill_level' => 'confirmé',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.username', 'newname')
            ->assertJsonPath('user.skill_level', 'confirmé');
    }

    public function test_update_password(): void
    {
        $user = User::factory()->create();
        $token = $this->generateToken($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/user', [
                'password' => 'newpassword123',
            ]);

        $response->assertStatus(200);
    }

    public function test_update_without_auth_returns_401(): void
    {
        $response = $this->putJson('/api/user', ['username' => 'test']);
        $response->assertStatus(401);
    }

    private function generateToken(User $user): string
    {
        $payload = [
            'iss' => url('/'),
            'sub' => $user->id,
            'iat' => now()->timestamp,
            'exp' => now()->addDays(7)->timestamp,
        ];

        return \Firebase\JWT\JWT::encode($payload, Config::get('services.jwt.secret'), 'HS256');
    }
}
