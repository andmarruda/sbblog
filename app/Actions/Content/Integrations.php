<?php

namespace App\Actions\Content;

use App\Enums\IntegrationScope;
use App\Models\Blog;
use App\Models\Integration;
use App\Publishing\EventRecorder;
use App\Publishing\Events\IntegrationsUpdated;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

class Integrations
{
    public function __construct(
        private readonly EventRecorder $events,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Blog $blog, array $data, ?Integration $integration = null): Integration
    {
        return DB::transaction(function () use ($blog, $data, $integration) {
            $previous = $integration === null ? null : [$integration->scope, $this->target($integration)];

            $integration ??= new Integration(['blog_id' => $blog->id]);
            $integration->fill($data)->save();

            // Moving between scopes changes two lists.
            if ($previous !== null && $previous !== [$integration->scope, $this->target($integration)]) {
                $this->emit($blog, $previous[0], $previous[1]);
            }

            $this->emit($blog, $integration->scope, $this->target($integration));
            $this->audit->record('integration.saved', $integration, ['provider' => $integration->provider->value, 'scope' => $integration->scope->value], blog: $blog);

            return $integration;
        });
    }

    public function delete(Blog $blog, Integration $integration): void
    {
        DB::transaction(function () use ($blog, $integration) {
            $integration->delete();

            $this->emit($blog, $integration->scope, $this->target($integration));
            $this->audit->record('integration.deleted', null, ['provider' => $integration->provider->value], blog: $blog);
        });
    }

    private function emit(Blog $blog, IntegrationScope $scope, ?string $target): void
    {
        $integrations = Integration::query()
            ->whereBelongsTo($blog)
            ->where('scope', $scope)
            ->where('enabled', true)
            ->when($scope === IntegrationScope::Author, fn ($query) => $query->where('author_profile_id', $target))
            ->when($scope === IntegrationScope::Article, fn ($query) => $query->where('article_id', $target))
            ->orderBy('created_at')
            ->get();

        $this->events->record($blog, new IntegrationsUpdated($blog->id, $scope, $target, $integrations));
    }

    private function target(Integration $integration): ?string
    {
        return match ($integration->scope) {
            IntegrationScope::Author => $integration->author_profile_id,
            IntegrationScope::Article => $integration->article_id,
            IntegrationScope::Blog => null,
        };
    }
}
