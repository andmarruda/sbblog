<?php

namespace App\Providers;

use App\Authorization\PermissionResolver;
use App\Authorization\Permissions;
use App\Authorization\TwoFactorRequirement;
use App\Listeners\AuthEventSubscriber;
use App\Models;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(PermissionResolver::class);
        $this->app->scoped(TwoFactorRequirement::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureModels();

        Event::subscribe(AuthEventSubscriber::class);

        $this->configureAuthorization();
        $this->configureRateLimits();
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
        // Lazy loading in a loop is an N+1 query; make it fail loudly outside production.
        Model::preventLazyLoading(! app()->isProduction());

        Relation::enforceMorphMap([
            'app' => Models\App::class,
            'app_installation' => Models\AppInstallation::class,
            'invitation' => Models\Invitation::class,
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
            'snapshot' => Models\Snapshot::class,
            'role' => Models\Role::class,
            'social_link' => Models\SocialLink::class,
            'tag' => Models\Tag::class,
            'user' => Models\User::class,
            'workspace' => Models\Workspace::class,
        ]);
    }

    /**
     * Permission keys ("articles.publish") are gate abilities, checked against a Blog or
     * Workspace: `$user->can('articles.publish', $blog)` or `->can('blog.settings', 'blog')`.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(function (Models\User $user, string $ability, array $arguments) {
            if (! Permissions::exists($ability)) {
                return null;
            }

            $scope = $arguments[0] ?? null;

            if (! $scope instanceof Models\Blog && ! $scope instanceof Models\Workspace) {
                return false;
            }

            if (app(TwoFactorRequirement::class)->blocks($user, $scope, $ability)) {
                return Response::deny(__('Enable two-factor authentication to do this.'));
            }

            return app(PermissionResolver::class)->has($user, $ability, $scope);
        });
    }

    protected function configureRateLimits(): void
    {
        // Per caller: a person or an app installation (not Authenticatable, so use the model key).
        RateLimiter::for('api', function (Request $request) {
            $caller = $request->user();

            return Limit::perMinute(120)->by($caller instanceof Model ? $caller::class.'|'.$caller->getKey() : (string) $request->ip());
        });

        RateLimiter::for('sync', function (Request $request) {
            $frontend = $request->attributes->get('frontend');

            return Limit::perMinute(120)->by($frontend instanceof Models\Frontend ? $frontend->id : (string) $request->ip());
        });
    }
}
