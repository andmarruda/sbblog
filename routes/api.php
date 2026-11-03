<?php

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Apps\OAuthController;
use App\Http\Middleware\AuthenticateFrontend;
use Illuminate\Support\Facades\Route;

// Frontends pull the publishing log with their sync token (docs/v2/events.md).
Route::middleware([AuthenticateFrontend::class, 'throttle:sync'])->prefix('sync')->group(function () {
    Route::get('/', [SyncController::class, 'events'])->name('sync.events');
    Route::post('ack', [SyncController::class, 'ack'])->name('sync.ack');
    Route::get('snapshot', [SyncController::class, 'snapshot'])->name('sync.snapshot');
});

Route::post('comments', [CommentController::class, 'store'])
    ->middleware([AuthenticateFrontend::class, 'throttle:sync'])
    ->name('api.comments.store');

Route::post('oauth/token', [OAuthController::class, 'token'])->middleware('throttle:30,1')->name('oauth.token');

// Content API v1 for apps and personal tokens (docs/v2/api/openapi.yaml).
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->name('api.v1.')->group(function () {
    Route::get('me', [ContentController::class, 'me'])->name('me');

    Route::prefix('blogs/{blog}')->whereUuid('blog')->group(function () {
        Route::get('articles', [ContentController::class, 'articles'])->name('articles');
        Route::get('articles/{article:id}', [ContentController::class, 'article'])->name('articles.show');
        Route::put('articles/{article:id}/draft', [ContentController::class, 'saveDraft'])->name('articles.draft');
        Route::post('articles/{article:id}/suggestions', [ContentController::class, 'suggest'])->name('articles.suggestions');
        Route::post('media', [ContentController::class, 'upload'])->name('media');
        Route::get('taxonomy', [ContentController::class, 'taxonomy'])->name('taxonomy');
    });
});
