<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\CharacterController;

Route::get('/test', function () {
    return response()->json(['message' => 'API OK']);
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/forums', [ForumController::class, 'index']);
Route::get('/forums/{id}', [ForumController::class, 'show']);
Route::get('/forums/{id}/posts', [ForumController::class, 'posts']);
Route::get('/players', [PlayerController::class, 'index']);
Route::get('/characters', [CharacterController::class, 'index']);
