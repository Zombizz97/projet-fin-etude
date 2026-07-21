<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRelationship;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class UserController extends Controller
{
    public function show(int $id, Request $request): JsonResponse
    {
        $user = User::with(['characters.character'])->findOrFail($id);

        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $secret = Config::get('services.jwt.secret');
            if ($secret) {
                try {
                    $decoded = JWT::decode(substr($authHeader, 7), new Key($secret, 'HS256'));
                    $currentUserId = $decoded->sub ?? null;
                    if ($currentUserId && $currentUserId !== $id) {
                        $rel = UserRelationship::where('user_id', $currentUserId)
                            ->where('related_user_id', $id)
                            ->first();
                        $user->setAttribute('friendship_status', $rel ? $rel->type : null);
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        return response()->json($user);
    }
}
