<?php

namespace App\Providers;

use App\Models;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureModels();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure Eloquent. Morph types are stored as stable aliases, never class names, since
     * they are written into tokens, audit logs and events.
     */
    protected function configureModels(): void
    {
        Relation::enforceMorphMap([
            'app' => Models\App::class,
            'app_installation' => Models\AppInstallation::class,
            'article' => Models\Article::class,
            'article_revision' => Models\ArticleRevision::class,
            'article_suggestion' => Models\ArticleSuggestion::class,
            'author_profile' => Models\AuthorProfile::class,
            'blog' => Models\Blog::class,
            'category' => Models\Category::class,
            'comment' => Models\Comment::class,
            'event_transport' => Models\EventTransport::class,
            'frontend' => Models\Frontend::class,
            'integration' => Models\Integration::class,
            'media' => Models\Media::class,
            'role' => Models\Role::class,
            'social_link' => Models\SocialLink::class,
            'tag' => Models\Tag::class,
            'user' => Models\User::class,
            'workspace' => Models\Workspace::class,
        ]);
    }
}
