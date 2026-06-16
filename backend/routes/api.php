<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BookGenerationController;
use App\Http\Controllers\ChildPhotoController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});

// Public endpoints
Route::get('/auth/google/url', [AuthController::class, 'getGoogleUrl']);
Route::post('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);

// Protected endpoints
Route::middleware('auth.api')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/language', [AuthController::class, 'updateLanguage']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/billing/checkout', [BillingController::class, 'checkout']);
    Route::post('/billing/portal', [BillingController::class, 'portal']);

    Route::get('/templates/catalog', [TemplateController::class, 'catalog']);
    Route::get('/books/history', [BookGenerationController::class, 'index']);
    Route::get('/books/{id}', [BookGenerationController::class, 'show'])->whereNumber('id');
    Route::post('/books/{id}/retry-illustrations', [BookGenerationController::class, 'retryIllustrations'])->whereNumber('id');
    Route::post('/books/generate', [BookGenerationController::class, 'generate'])
        ->middleware('throttle:books-generate');
    Route::post('/photos/upload', [ChildPhotoController::class, 'upload'])
        ->middleware('throttle:photos-upload');
});
