<?php

use App\Http\Controllers\Blogs\BlogSocialLinkController;
use App\Http\Controllers\Content\ArticleController;
use App\Http\Controllers\Content\ArticlePreviewController;
use App\Http\Controllers\Content\ArticlePublicationController;
use App\Http\Controllers\Content\ArticleRevisionController;
use App\Http\Controllers\Content\ArticleTranslationController;
use App\Http\Controllers\Content\AuthorController;
use App\Http\Controllers\Content\CategoryController;
use App\Http\Controllers\Content\CommentController as ContentCommentController;
use App\Http\Controllers\Content\IntegrationController;
use App\Http\Controllers\Content\MediaController;
use App\Http\Controllers\Content\SuggestionController;
use App\Http\Controllers\Content\TagController;
use App\Http\Controllers\Publishing\EventLogController;
use App\Http\Controllers\Publishing\PublishingController;
use Illuminate\Support\Facades\Route;

/*
| Blog content, included inside the /{workspace}/{blog} group of routes/tenant.php.
| Models use custom keys ({article:id}…) so Laravel scopes each one to the blog.
*/

Route::prefix('articles')->name('articles.')->group(function () {
    Route::get('/', [ArticleController::class, 'index'])->name('index');
    Route::post('/', [ArticleController::class, 'store'])->middleware('throttle:30,1')->name('store');
    Route::post('bulk', [ArticleController::class, 'bulk'])->name('bulk');

    Route::prefix('{article:id}')->group(function () {
        Route::get('/', [ArticleController::class, 'edit'])->name('edit');
        Route::put('draft', [ArticleController::class, 'saveDraft'])->middleware('throttle:120,1')->name('draft');
        Route::patch('/', [ArticleController::class, 'updateDetails'])->name('update');
        Route::delete('/', [ArticleController::class, 'destroy'])->name('destroy');
        Route::post('restore', [ArticleController::class, 'restore'])->withTrashed()->name('restore');

        Route::post('publish', [ArticlePublicationController::class, 'publish'])->name('publish');
        Route::post('schedule', [ArticlePublicationController::class, 'schedule'])->name('schedule');
        Route::post('unpublish', [ArticlePublicationController::class, 'unpublish'])->name('unpublish');
        Route::post('archive', [ArticlePublicationController::class, 'archive'])->name('archive');
        Route::post('review', [ArticlePublicationController::class, 'submitForReview'])->name('review');

        Route::get('preview', ArticlePreviewController::class)->name('preview');
        Route::get('revisions', [ArticleRevisionController::class, 'index'])->name('revisions');
        Route::post('revisions/{revision:id}/restore', [ArticleRevisionController::class, 'restore'])->name('revisions.restore');
        Route::post('translations', [ArticleTranslationController::class, 'store'])->name('translations.store');

        Route::get('suggestions', [SuggestionController::class, 'index'])->name('suggestions.index');
        Route::post('suggestions', [SuggestionController::class, 'store'])->middleware('throttle:30,1')->name('suggestions.store');
        Route::get('suggestions/{suggestion:id}', [SuggestionController::class, 'show'])->name('suggestions.show');
        Route::post('suggestions/{suggestion:id}/accept', [SuggestionController::class, 'accept'])->name('suggestions.accept');
        Route::post('suggestions/{suggestion:id}/reject', [SuggestionController::class, 'reject'])->name('suggestions.reject');
        Route::post('suggestions/{suggestion:id}/comments', [SuggestionController::class, 'comment'])->middleware('throttle:60,1')->name('suggestions.comment');
    });
});

Route::prefix('media')->name('media.')->group(function () {
    Route::get('/', [MediaController::class, 'index'])->middleware('can:articles.view,blog')->name('index');
    Route::get('picker', [MediaController::class, 'picker'])->middleware('can:articles.view,blog')->name('picker');
    Route::post('/', [MediaController::class, 'store'])->middleware(['can:media.upload,blog', 'throttle:60,1'])->name('store');
    Route::patch('{media:id}', [MediaController::class, 'update'])->name('update');
    Route::delete('{media:id}', [MediaController::class, 'destroy'])->name('destroy');
});

Route::prefix('categories')->name('categories.')->middleware('can:categories.manage,blog')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::patch('{category:id}', [CategoryController::class, 'update'])->name('update');
    Route::post('{category:id}/move', [CategoryController::class, 'move'])->name('move');
    Route::delete('{category:id}', [CategoryController::class, 'destroy'])->name('destroy');
    Route::post('{category:id}/restore', [CategoryController::class, 'restore'])->withTrashed()->name('restore');
});

Route::prefix('tags')->name('tags.')->middleware('can:tags.manage,blog')->group(function () {
    Route::get('/', [TagController::class, 'index'])->name('index');
    Route::post('/', [TagController::class, 'store'])->name('store');
    Route::patch('{tag:id}', [TagController::class, 'update'])->name('update');
    Route::post('{tag:id}/merge', [TagController::class, 'merge'])->name('merge');
    Route::delete('{tag:id}', [TagController::class, 'destroy'])->name('destroy');
});

Route::prefix('authors')->name('authors.')->middleware('can:authors.manage,blog')->group(function () {
    Route::get('/', [AuthorController::class, 'index'])->name('index');
    Route::post('/', [AuthorController::class, 'store'])->name('store');
    Route::patch('{authorProfile:id}', [AuthorController::class, 'update'])->name('update');
    Route::delete('{authorProfile:id}', [AuthorController::class, 'destroy'])->name('destroy');
});

Route::get('settings/social', [BlogSocialLinkController::class, 'edit'])->middleware('can:blog.settings,blog')->name('blogs.settings.social');
Route::put('settings/social', [BlogSocialLinkController::class, 'update'])->middleware('can:blog.settings,blog')->name('blogs.settings.social.update');

Route::prefix('integrations')->name('integrations.')->middleware('can:integrations.manage,blog')->group(function () {
    Route::get('/', [IntegrationController::class, 'index'])->name('index');
    Route::post('/', [IntegrationController::class, 'store'])->name('store');
    Route::patch('{integration:id}', [IntegrationController::class, 'update'])->name('update');
    Route::post('{integration:id}/toggle', [IntegrationController::class, 'toggle'])->name('toggle');
    Route::delete('{integration:id}', [IntegrationController::class, 'destroy'])->name('destroy');
});

Route::prefix('publishing')->name('publishing.')->middleware('can:frontends.manage,blog')->group(function () {
    Route::get('/', [PublishingController::class, 'index'])->name('index');
    Route::get('events', EventLogController::class)->name('events');
    Route::post('frontends', [PublishingController::class, 'storeFrontend'])->name('frontends.store');
    Route::patch('frontends/{frontend:id}', [PublishingController::class, 'updateFrontend'])->name('frontends.update');
    Route::post('frontends/{frontend:id}/token', [PublishingController::class, 'rotateToken'])->middleware('throttle:10,1')->name('frontends.token');
    Route::delete('frontends/{frontend:id}', [PublishingController::class, 'destroyFrontend'])->name('frontends.destroy');
    Route::post('snapshots', [PublishingController::class, 'buildSnapshot'])->middleware('throttle:6,1')->name('snapshots.store');
    Route::post('transports', [PublishingController::class, 'storeTransport'])->name('transports.store');
    Route::patch('transports/{eventTransport:id}', [PublishingController::class, 'updateTransport'])->name('transports.update');
    Route::post('transports/{eventTransport:id}/test', [PublishingController::class, 'testTransport'])->middleware('throttle:10,1')->name('transports.test');
    Route::delete('transports/{eventTransport:id}', [PublishingController::class, 'destroyTransport'])->name('transports.destroy');
});

Route::prefix('comments')->name('comments.')->middleware('can:comments.moderate,blog')->group(function () {
    Route::get('/', [ContentCommentController::class, 'index'])->name('index');
    Route::post('bulk', [ContentCommentController::class, 'bulk'])->name('bulk');
    Route::post('{comment:id}/moderate', [ContentCommentController::class, 'moderate'])->name('moderate');
    Route::post('{comment:id}/reply', [ContentCommentController::class, 'reply'])->name('reply');
    Route::delete('{comment:id}', [ContentCommentController::class, 'destroy'])->name('destroy');
    Route::post('{comment:id}/restore', [ContentCommentController::class, 'restore'])->withTrashed()->name('restore');
});
