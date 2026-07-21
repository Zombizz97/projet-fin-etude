<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRelationship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    private function user(Request $request): User
    {
        return $request->attributes->get('auth_user');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $friendIds = UserRelationship::where('user_id', $user->id)
            ->where('type', 'friend')
            ->pluck('related_user_id');
        $friends = User::whereIn('id', $friendIds)->get(['id', 'username', 'skill_level']);

        return response()->json($friends);
    }

    public function pending(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $requests = UserRelationship::where('related_user_id', $user->id)
            ->where('type', 'pending')
            ->with('user:id,username,skill_level')
            ->get()
            ->pluck('user');

        return response()->json($requests);
    }

    public function sent(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $requests = UserRelationship::where('user_id', $user->id)
            ->where('type', 'pending')
            ->with('relatedUser:id,username,skill_level')
            ->get()
            ->pluck('relatedUser');

        return response()->json($requests);
    }

    public function blocked(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $blockedIds = UserRelationship::where('user_id', $user->id)
            ->where('type', 'blocked')
            ->pluck('related_user_id');
        $blocked = User::whereIn('id', $blockedIds)->get(['id', 'username']);

        return response()->json($blocked);
    }

    public function sendRequest(int $id, Request $request): JsonResponse
    {
        $user = $this->user($request);
        if ($user->id === $id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous ajouter vous-même'], 400);
        }

        $target = User::find($id);
        if (! $target) {
            return response()->json(['message' => 'Utilisateur introuvable'], 404);
        }

        $existing = UserRelationship::where('user_id', $user->id)
            ->where('related_user_id', $id)
            ->first();

        if ($existing) {
            if ($existing->type === 'blocked') {
                return response()->json(['message' => 'Cet utilisateur est bloqué'], 400);
            }
            if ($existing->type === 'pending') {
                return response()->json(['message' => 'Demande déjà envoyée'], 400);
            }
            if ($existing->type === 'friend') {
                return response()->json(['message' => 'Vous êtes déjà amis'], 400);
            }
        }

        UserRelationship::create([
            'user_id' => $user->id,
            'related_user_id' => $id,
            'type' => 'pending',
        ]);

        return response()->json(['message' => 'Demande d\'ami envoyée'], 201);
    }

    public function accept(int $id, Request $request): JsonResponse
    {
        $user = $this->user($request);

        $rel = UserRelationship::where('user_id', $id)
            ->where('related_user_id', $user->id)
            ->where('type', 'pending')
            ->first();

        if (! $rel) {
            return response()->json(['message' => 'Aucune demande en attente'], 404);
        }

        $rel->update(['type' => 'friend']);
        UserRelationship::firstOrCreate(
            ['user_id' => $user->id, 'related_user_id' => $id],
            ['type' => 'friend']
        );

        return response()->json(['message' => 'Demande d\'ami acceptée']);
    }

    public function decline(int $id, Request $request): JsonResponse
    {
        $user = $this->user($request);

        $deleted = UserRelationship::where('user_id', $id)
            ->where('related_user_id', $user->id)
            ->where('type', 'pending')
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Aucune demande en attente'], 404);
        }

        return response()->json(['message' => 'Demande refusée']);
    }

    public function remove(int $id, Request $request): JsonResponse
    {
        $user = $this->user($request);

        UserRelationship::where(function ($q) use ($user, $id) {
            $q->where('user_id', $user->id)->where('related_user_id', $id)->where('type', 'friend');
        })->orWhere(function ($q) use ($user, $id) {
            $q->where('user_id', $id)->where('related_user_id', $user->id)->where('type', 'friend');
        })->delete();

        return response()->json(['message' => 'Ami supprimé']);
    }

    public function block(int $id, Request $request): JsonResponse
    {
        $user = $this->user($request);
        if ($user->id === $id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous bloquer vous-même'], 400);
        }

        $target = User::find($id);
        if (! $target) {
            return response()->json(['message' => 'Utilisateur introuvable'], 404);
        }

        UserRelationship::where(function ($q) use ($user, $id) {
            $q->where('user_id', $user->id)->where('related_user_id', $id);
        })->orWhere(function ($q) use ($user, $id) {
            $q->where('user_id', $id)->where('related_user_id', $user->id);
        })->delete();

        UserRelationship::create([
            'user_id' => $user->id,
            'related_user_id' => $id,
            'type' => 'blocked',
        ]);

        return response()->json(['message' => 'Utilisateur bloqué']);
    }

    public function unblock(int $id, Request $request): JsonResponse
    {
        $user = $this->user($request);

        $deleted = UserRelationship::where('user_id', $user->id)
            ->where('related_user_id', $id)
            ->where('type', 'blocked')
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Cet utilisateur n\'est pas bloqué'], 404);
        }

        return response()->json(['message' => 'Utilisateur débloqué']);
    }
}
