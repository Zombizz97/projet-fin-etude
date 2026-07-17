<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/metrics', [MetricsController::class, 'index']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('jwt');
Route::put('/auth/me', [AuthController::class, 'me'])->middleware('jwt');

Route::get('/forums', [ForumController::class, 'index']);
Route::post('/forums', [ForumController::class, 'store'])->middleware('jwt');
Route::get('/forums/{id}', [ForumController::class, 'show']);
Route::get('/forums/{id}/posts', [ForumController::class, 'posts']);
Route::post('/forums/{id}/posts', [ForumController::class, 'storePost'])->middleware('jwt');
Route::post('/posts/{id}/vote', [ForumController::class, 'vote'])->middleware('jwt');
Route::get('/players', [PlayerController::class, 'index']);
Route::get('/characters', [CharacterController::class, 'index']);
Route::put('/user', [AuthController::class, 'update'])->middleware('jwt');

Route::get('/users/{id}', [UserController::class, 'show']);

Route::get('/friends', [FriendController::class, 'index'])->middleware('jwt');
Route::get('/friends/pending', [FriendController::class, 'pending'])->middleware('jwt');
Route::get('/friends/sent', [FriendController::class, 'sent'])->middleware('jwt');
Route::get('/friends/blocked', [FriendController::class, 'blocked'])->middleware('jwt');
Route::post('/friends/{id}', [FriendController::class, 'sendRequest'])->middleware('jwt');
Route::post('/friends/{id}/accept', [FriendController::class, 'accept'])->middleware('jwt');
Route::delete('/friends/{id}/accept', [FriendController::class, 'decline'])->middleware('jwt');
Route::delete('/friends/{id}', [FriendController::class, 'remove'])->middleware('jwt');
Route::post('/friends/{id}/block', [FriendController::class, 'block'])->middleware('jwt');
Route::post('/friends/{id}/unblock', [FriendController::class, 'unblock'])->middleware('jwt');
