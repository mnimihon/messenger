<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;


Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::apiResource('conversations', ConversationController::class);
    Route::apiResource('messages', MessageController::class);

    Route::post('/messages/mark-read', [MessageController::class, 'markAsRead']);
});
