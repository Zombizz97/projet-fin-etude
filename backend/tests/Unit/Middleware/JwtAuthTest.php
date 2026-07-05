<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\JwtAuth;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtAuthTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-secret-key-12345';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.jwt.secret', $this->secret);
    }

    public function test_missing_authorization_header_returns_401(): void
    {
        $middleware = new JwtAuth();
        $request = Request::create('/api/auth/me', 'GET');

        $response = $middleware->handle($request, fn() => response()->json(['ok']));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getData()->message);
    }

    public function test_non_bearer_header_returns_401(): void
    {
        $middleware = new JwtAuth();
        $request = Request::create('/api/auth/me', 'GET');
        $request->headers->set('Authorization', 'Basic abc123');

        $response = $middleware->handle($request, fn() => response()->json(['ok']));

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_invalid_token_returns_401(): void
    {
        $middleware = new JwtAuth();
        $request = Request::create('/api/auth/me', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid-token');

        $response = $middleware->handle($request, fn() => response()->json(['ok']));

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_valid_token_sets_auth_user(): void
    {
        $user = User::factory()->create();
        $token = JWT::encode(
            ['sub' => $user->id, 'iat' => now()->timestamp, 'exp' => now()->addDay()->timestamp],
            $this->secret,
            'HS256'
        );

        $middleware = new JwtAuth();
        $request = Request::create('/api/auth/me', 'GET');
        $request->headers->set('Authorization', "Bearer $token");

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['user_id' => $req->attributes->get('auth_user')->id]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($user->id, $response->getData()->user_id);
    }

    public function test_token_without_sub_returns_401(): void
    {
        $token = JWT::encode(
            ['iat' => now()->timestamp, 'exp' => now()->addDay()->timestamp],
            $this->secret,
            'HS256'
        );

        $middleware = new JwtAuth();
        $request = Request::create('/api/auth/me', 'GET');
        $request->headers->set('Authorization', "Bearer $token");

        $response = $middleware->handle($request, fn() => response()->json(['ok']));

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_token_for_nonexistent_user_returns_401(): void
    {
        $token = JWT::encode(
            ['sub' => 99999, 'iat' => now()->timestamp, 'exp' => now()->addDay()->timestamp],
            $this->secret,
            'HS256'
        );

        $middleware = new JwtAuth();
        $request = Request::create('/api/auth/me', 'GET');
        $request->headers->set('Authorization', "Bearer $token");

        $response = $middleware->handle($request, fn() => response()->json(['ok']));

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_missing_secret_returns_500(): void
    {
        Config::set('services.jwt.secret', null);

        $middleware = new JwtAuth();
        $request = Request::create('/api/auth/me', 'GET');
        $request->headers->set('Authorization', 'Bearer somerandomtoken');

        $response = $middleware->handle($request, fn() => response()->json(['ok']));

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Server misconfigured', $response->getData()->message);
    }
}
