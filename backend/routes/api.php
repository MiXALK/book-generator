<?php

use App\Http\Controllers\Admin\AdminContentController;
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

Route::get('/books/{id}/pages/{page}/image', [BookGenerationController::class, 'pageImage'])
    ->whereNumber('id')
    ->whereNumber('page')
    ->middleware('signed')
    ->name('books.page-image');

// Protected endpoints
Route::middleware('auth.api')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/language', [AuthController::class, 'updateLanguage']);
    Route::delete('/user', [AuthController::class, 'destroy']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/billing/checkout', [BillingController::class, 'checkout']);
    Route::post('/billing/portal', [BillingController::class, 'portal']);

    Route::get('/templates/catalog', [TemplateController::class, 'catalog']);
    Route::get('/books/history', [BookGenerationController::class, 'index']);
    Route::get('/books/{id}', [BookGenerationController::class, 'show'])->whereNumber('id');
    Route::delete('/books/{id}', [BookGenerationController::class, 'destroy'])->whereNumber('id');
    Route::post('/books/{id}/retry-illustrations', [BookGenerationController::class, 'retryIllustrations'])
        ->whereNumber('id')
        ->middleware('throttle:books-retry-illustrations');
    Route::post('/books/generate', [BookGenerationController::class, 'generate'])
        ->middleware('throttle:books-generate');
    Route::post('/photos/upload', [ChildPhotoController::class, 'upload'])
        ->middleware('throttle:photos-upload');

    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/review-queue', [AdminContentController::class, 'reviewQueue']);
        Route::get('/quality-config', [AdminContentController::class, 'qualityConfig']);

        Route::get('/goals', [AdminContentController::class, 'listGoals']);
        Route::post('/goals', [AdminContentController::class, 'storeGoal']);
        Route::get('/goals/{id}', [AdminContentController::class, 'showGoal'])->whereNumber('id');
        Route::put('/goals/{id}', [AdminContentController::class, 'updateGoal'])->whereNumber('id');
        Route::delete('/goals/{id}', [AdminContentController::class, 'destroyGoal'])->whereNumber('id');

        Route::get('/templates', [AdminContentController::class, 'listTemplates']);
        Route::post('/templates', [AdminContentController::class, 'storeTemplate']);
        Route::get('/templates/{id}', [AdminContentController::class, 'showTemplate'])->whereNumber('id');
        Route::put('/templates/{id}', [AdminContentController::class, 'updateTemplate'])->whereNumber('id');
        Route::delete('/templates/{id}', [AdminContentController::class, 'destroyTemplate'])->whereNumber('id');
        Route::post('/templates/{id}/submit-review', [AdminContentController::class, 'submitTemplateReview'])->whereNumber('id');
        Route::post('/templates/{id}/publish', [AdminContentController::class, 'publishTemplate'])->whereNumber('id');
        Route::get('/templates/{id}/preview', [AdminContentController::class, 'previewTemplate'])->whereNumber('id');

        Route::get('/prompts', [AdminContentController::class, 'listPrompts']);
        Route::post('/prompts', [AdminContentController::class, 'storePrompt']);
        Route::get('/prompts/{id}', [AdminContentController::class, 'showPrompt'])->whereNumber('id');
        Route::put('/prompts/{id}', [AdminContentController::class, 'updatePrompt'])->whereNumber('id');
        Route::delete('/prompts/{id}', [AdminContentController::class, 'destroyPrompt'])->whereNumber('id');
        Route::post('/prompts/{id}/submit-review', [AdminContentController::class, 'submitPromptReview'])->whereNumber('id');
        Route::post('/prompts/{id}/publish', [AdminContentController::class, 'publishPrompt'])->whereNumber('id');
        Route::post('/prompts/{id}/ratings', [AdminContentController::class, 'storePromptRating'])->whereNumber('id');
        Route::get('/prompts/{id}/preview', [AdminContentController::class, 'previewPrompt'])->whereNumber('id');

        Route::get('/layouts', [AdminContentController::class, 'listLayouts']);
        Route::post('/layouts', [AdminContentController::class, 'storeLayout']);
        Route::get('/layouts/{id}', [AdminContentController::class, 'showLayout'])->whereNumber('id');
        Route::put('/layouts/{id}', [AdminContentController::class, 'updateLayout'])->whereNumber('id');
        Route::delete('/layouts/{id}', [AdminContentController::class, 'destroyLayout'])->whereNumber('id');
        Route::post('/layouts/{id}/submit-review', [AdminContentController::class, 'submitLayoutReview'])->whereNumber('id');
        Route::post('/layouts/{id}/publish', [AdminContentController::class, 'publishLayout'])->whereNumber('id');
        Route::get('/layouts/{id}/preview', [AdminContentController::class, 'previewLayout'])->whereNumber('id');
    });
});
