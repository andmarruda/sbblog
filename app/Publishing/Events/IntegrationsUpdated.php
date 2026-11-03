<?php

namespace App\Publishing\Events;

use App\Enums\IntegrationScope;
use App\Models\Integration;
use Illuminate\Support\Collection;

/**
 * The integrations of one scope changed. Carries the full list for that scope, so frontends
 * replace it instead of patching.
 */
final class IntegrationsUpdated extends PublishableEvent
{
    /**
     * @param  Collection<int, Integration>  $integrations  Enabled integrations of the scope.
     */
    public function __construct(
        private readonly string $blogId,
        private readonly IntegrationScope $scope,
        private readonly ?string $targetId,
        private readonly Collection $integrations,
    ) {}

    public static function type(): string
    {
        return 'integrations.updated';
    }

    public function aggregateType(): string
    {
        return 'integrations';
    }

    public function aggregateId(): string
    {
        return $this->targetId ?? $this->blogId;
    }

    public function payload(): array
    {
        return [
            'scope' => $this->scope->value,
            'author_profile_id' => $this->scope === IntegrationScope::Author ? $this->targetId : null,
            'article_id' => $this->scope === IntegrationScope::Article ? $this->targetId : null,
            'integrations' => $this->integrations->map(fn (Integration $integration) => [
                'id' => $integration->id,
                'provider' => $integration->provider->value,
                'config' => $integration->config,
                'placement' => $integration->placement->value,
                'consent_category' => $integration->consent_category->value,
                'page_rule' => $integration->page_rule,
            ])->values()->all(),
        ];
    }
}
