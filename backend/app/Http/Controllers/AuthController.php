<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /** Inscription d'un utilisateur */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required','string','max:50','unique:users,username'],
            'email' => ['required','string','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6'],
            'skill_level' => ['nullable','in:débutant,intermédiaire,confirmé,professionnel'],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'skill_level' => $validated['skill_level'] ?? null,
        ]);

        $token = method_exists($user, 'createToken') ? $user->createToken('api')->plainTextToken : null;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    /** Connexion d'un utilisateur */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        $token = method_exists($user, 'createToken') ? $user->createToken('api')->plainTextToken : null;

        return response()->json(['user' => $user, 'token' => $token]);
    }
}
