<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});

// Public endpoints
Route::get('/auth/google/url', [AuthController::class, 'getGoogleUrl']);
Route::post('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Protected endpoints
Route::middleware('auth.api')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/language', [AuthController::class, 'updateLanguage']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
