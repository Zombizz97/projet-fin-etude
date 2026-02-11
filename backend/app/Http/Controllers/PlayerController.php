<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class PlayerController extends Controller
{
    /** Liste les joueurs */
    public function index(): JsonResponse
    {
        $players = User::with(['characters.character'])->get();
        return response()->json($players);
    }
}

