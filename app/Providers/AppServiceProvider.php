<?php

namespace App\Providers;

use App\Http\View\Composers\AdminTemplateComposer;
use App\Http\View\Composers\ArticleFundamentalsComposer;
use App\Http\View\Composers\ArticleListComposer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\Http\View\Composers\PublicTemplateComposer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();
        View::composer(['templates.adminTemplate'], AdminTemplateComposer::class);
        View::composer(['newArticle'], ArticleFundamentalsComposer::class);
        View::composer(['articleList'], ArticleListComposer::class);
        View::composer(['templates.publicTemplate'], PublicTemplateComposer::class);
    }
}
