<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\ArticleController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', '\App\Http\Controllers\PublicController@latestPage')->name('latestPage');
Route::get('/byCategory/{category?}/{id?}', '\App\Http\Controllers\PublicController@latestPage')->name('latestPageCategory')->where('id', '[0-9]+');
Route::post('/search', '\App\Http\Controllers\PublicController@latestPageSearch')->name('latestPageSearch');

Route::get('/article/{friendly}/{id}', '\App\Http\Controllers\PublicController@articlePage')->name('article')->where('id', '[0-9]+')->where('friendly', '[0-9\-A-Za-z]+');
Route::post('/article', '\App\Http\Controllers\PublicController@articlePageComment')->name('articleComment');

Route::post('/visitInit', '\App\Http\Controllers\ArticleController@articleVisitInit')->name('visitInit');
Route::post('/visitEnd', '\App\Http\Controllers\ArticleController@articleVisitEnd')->name('visitEnd');

Route::prefix('/admin')->group(function() {
    //Navigation
    Route::get('/', '\App\Http\Controllers\AdminController@loginInterface')->name('admin.login');

    //user
    Route::get('/user/{id?}', '\App\Http\Controllers\UserController@userInterface')->where('id', '[0-9]+')->name('admin.user');
    Route::get('/changeLang/{id}', '\App\Http\Controllers\UserController@setPreferredLang')->where('id', '[0-9]+')->name('admin.changeLang');
    Route::post('/user', '\App\Http\Controllers\UserController@userFormPost')->name('admin.userPost');
    Route::post('/userSearch', '\App\Http\Controllers\UserController@userSearch')->name('admin.userSearch')->middleware('sbauth');
    Route::post('/userAlterPass', '\App\Http\Controllers\UserController@alterPassword')->name('admin.userAlterPass')->middleware('sbauth');

    //Login && Logout
    Route::post('/checkLogin', '\App\Http\Controllers\UserController@login')->name('admin.checkLogin');
    Route::get('/logout', '\App\Http\Controllers\UserController@logout')->name('admin.logout');

    //general configuration
    Route::get('/general', '\App\Http\Controllers\GeneralController@generalInterface')->name('admin.general')->middleware('sbauth');;
    Route::post('/generalPost', '\App\Http\Controllers\GeneralController@generalSave')->name('admin.generalPost')->middleware('sbauth');;

    //With sbauth middleware
    Route::get('/dashboard', '\App\Http\Controllers\AdminController@dashboardInterface')->name('admin.dashboard')->middleware('sbauth');

    //Article
    Route::get('/articleList', '\App\Http\Controllers\ArticleController@articleListInterface')->name('admin.articleList')->middleware('sbauth');
    Route::post('/articleList', '\App\Http\Controllers\ArticleController@articleListInterfaceSearch')->middleware('sbauth');
    Route::get('/newArticle/{id?}', '\App\Http\Controllers\ArticleController@articleFormInterface')->where('id', '[0-9]+')->name('admin.newArticle')->middleware('sbauth');
    Route::post('/newArticle', '\App\Http\Controllers\ArticleController@articleFormPost')->name('admin.newArticlePost')->middleware('sbauth');
    Route::post('/article/comment/enable-disable', [ArticleController::class, 'enableDisableComment'])->name('admin.article.comment.action')->middleware('sbauth');

    //Category
    Route::get('/category/{id?}', '\App\Http\Controllers\CategoryController@categoryInterface')->where('id', '[0-9]+')->name('admin.category')->middleware('sbauth');
    Route::post('/categorySearch', '\App\Http\Controllers\CategoryController@categorySearch')->name('admin.categorySearch')->middleware('sbauth');
    Route::post('/category', '\App\Http\Controllers\CategoryController@categoryFormPost')->name('admin.categoryPost')->middleware('sbauth');
});