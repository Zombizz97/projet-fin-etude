<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRelationship;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class PlayerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $players = User::with(['characters.character'])->get();

        $userId = $this->getOptionalUserId($request);
        if ($userId) {
            $rel = UserRelationship::where('user_id', $userId)
                ->whereIn('related_user_id', $players->pluck('id'))
                ->get()
                ->keyBy('related_user_id');

            foreach ($players as $player) {
                $r = $rel->get($player->id);
                $player->setAttribute('friendship_status', $r ? $r->type : null);
            }
        }

        return response()->json($players);
    }

    private function getOptionalUserId(Request $request): ?int
    {
        $authHeader = $request->header('Authorization');
        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        $token = substr($authHeader, 7);
        $secret = Config::get('services.jwt.secret');
        if (! $secret) {
            return null;
        }
        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            return $decoded->sub ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
