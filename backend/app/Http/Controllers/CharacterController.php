<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\JsonResponse;

class CharacterController extends Controller
{
    /** Liste tous les personnages */
    public function index(): JsonResponse
    {
        $characters = Character::query()->orderBy('name')->get();

        return response()->json($characters);
    }
}
