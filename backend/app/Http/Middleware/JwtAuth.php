<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;
use App\Models\User;

class JwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $token = substr($authHeader, 7);
        $secret = Config::get('services.jwt.secret');
        if (!$secret) {
            return response()->json(['message' => 'Server misconfigured'], 500);
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $userId = $decoded->sub ?? null;
            if (!$userId) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 401);
            }
            $request->attributes->set('auth_user', $user);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}

