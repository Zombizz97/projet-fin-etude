<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\MetricsController;

Route::get('/metrics', [MetricsController::class, 'index']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('jwt');
Route::put('/auth/me', [AuthController::class, 'me'])->middleware('jwt');

Route::get('/forums', [ForumController::class, 'index']);
Route::get('/forums/{id}', [ForumController::class, 'show']);
Route::get('/forums/{id}/posts', [ForumController::class, 'posts']);
Route::get('/players', [PlayerController::class, 'index']);
Route::get('/characters', [CharacterController::class, 'index']);
Route::put('/user', [AuthController::class, 'update'])->middleware('jwt');
