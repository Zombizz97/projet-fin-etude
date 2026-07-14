<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\User;
use App\Models\UserCharacter;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /** Inscription d'un utilisateur */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'skill_level' => ['nullable', 'in:débutant,intermédiaire,confirmé,professionnel'],
            'character_id' => ['nullable', 'integer', 'exists:characters,id'],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'skill_level' => $validated['skill_level'] ?? null,
        ]);

        // Associer le personnage principal si fourni
        if (! empty($validated['character_id'])) {
            $character = Character::find($validated['character_id']);
            if ($character) {
                UserCharacter::updateOrCreate(
                    ['user_id' => $user->id, 'character_id' => $character->id],
                    ['is_main' => true]
                );
            }
        }

        // Génération du token JWT
        $payload = [
            'iss' => url('/'), // émetteur
            'sub' => $user->id, // sujet: identifiant utilisateur
            'iat' => Carbon::now()->timestamp, // émis à
            'exp' => Carbon::now()->addDays(7)->timestamp, // expiration
        ];
        $secret = Config::get('services.jwt.secret') ?? env('JWT_SECRET') ?? env('APP_KEY');
        if (! $secret) {
            return response()->json(['message' => 'JWT_SECRET non défini sur le serveur'], 500);
        }
        $token = JWT::encode($payload, $secret, 'HS256');

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    /** Connexion d'un utilisateur */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $validated['username'])->first();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        // Génération du token JWT
        $payload = [
            'iss' => url('/'),
            'sub' => $user->id,
            'iat' => Carbon::now()->timestamp,
            'exp' => Carbon::now()->addDays(7)->timestamp,
        ];
        $secret = Config::get('services.jwt.secret') ?? env('JWT_SECRET') ?? env('APP_KEY');
        if (! $secret) {
            return response()->json(['message' => 'JWT_SECRET non défini sur le serveur'], 500);
        }
        $token = JWT::encode($payload, $secret, 'HS256');

        return response()->json(['user' => $user, 'token' => $token]);
    }

    /** Récupérer l'utilisateur connecté via JWT (middleware) */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->attributes->get('auth_user') ?? $request->user()]);
    }

    /** Mettre à jour le profil utilisateur (JWT requis) */
    public function update(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user');
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'max:50', 'unique:users,username,'.$user->id],
            'skill_level' => ['nullable', 'in:débutant,intermédiaire,confirmé,professionnel'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (array_key_exists('username', $validated)) {
            $user->username = $validated['username'];
        }
        if (array_key_exists('skill_level', $validated)) {
            $user->skill_level = $validated['skill_level'];
        }
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return response()->json(['user' => $user]);
    }
}
