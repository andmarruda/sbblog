<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;

Route::get('/', '\App\Http\Controllers\PublicController@latestPage')->name('latestPage');
Route::get('/byCategory/{category?}/{id?}', '\App\Http\Controllers\PublicController@latestPage')->name('latestPageCategory')->where('id', '[0-9]+');
Route::post('/search', '\App\Http\Controllers\PublicController@latestPageSearch')->name('latestPageSearch');

Route::get('/article/{friendly}/{id}', '\App\Http\Controllers\PublicController@articlePage')->name('article')->where('id', '[0-9]+')->where('friendly', '[0-9\-A-Za-z]+');
Route::post('/article', '\App\Http\Controllers\PublicController@articlePageComment')->name('articleComment');

Route::post('/visitInit', '\App\Http\Controllers\ArticleController@articleVisitInit')->name('visitInit');
Route::post('/visitEnd', '\App\Http\Controllers\ArticleController@articleVisitEnd')->name('visitEnd');

Route::prefix('/admin')->middleware(['auth', 'firstUserRedirect', 'language'])->group(function() {
    //exceptions of middleware
    Route::withoutMiddleware(['auth', 'firstUserRedirect'])->group(function() {
        Route::get('/', [AdminController::class, 'loginInterface'])->name('login');
        Route::post('/checkLogin', [UserController::class, 'login'])->name('admin.checkLogin');
        Route::get('/logout', [UserController::class, 'logout'])->name('admin.logout');
    });

    Route::get('/changeLang/{id}', [UserController::class, 'setPreferredLang'])->where('id', '[0-9]+')->name('admin.changeLang');
    Route::get('/dashboard', [AdminController::class, 'dashboardInterface'])->name('admin.dashboard');
    Route::resource('general', GeneralController::class)->only(['edit', 'update']);
    Route::resource('category', CategoryController::class)->except(['show']);
    Route::resource('user', UserController::class);    
    Route::post('/userAlterPass', [UserController::class, 'alterPassword'])->name('admin.userAlterPass');

    //Article
    Route::get('/newArticle/{id?}', '\App\Http\Controllers\ArticleController@articleFormInterface')->where('id', '[0-9]+')->name('admin.newArticle');
    Route::post('/newArticle', '\App\Http\Controllers\ArticleController@articleFormPost')->name('admin.newArticlePost');
    Route::post('/article/comment/enable-disable', [ArticleController::class, 'enableDisableComment'])->name('admin.article.comment.action');
    Route::get('/article/convertWebp/{id}', [ArticleController::class, 'convertWebp'])->where('id', '[0-9]+')->name('admin.article.convertWebp');

    Route::resource('article', ArticleController::class)->except(['show', 'create', 'edit', 'update']);
});