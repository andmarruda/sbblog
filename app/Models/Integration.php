<?php

namespace App\Models;

use App\Enums\ConsentCategory;
use App\Enums\IntegrationPlacement;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationScope;
use Database\Factories\IntegrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property IntegrationScope $scope
 * @property IntegrationProvider $provider
 * @property IntegrationPlacement $placement
 * @property ConsentCategory $consent_category
 * @property array<string, mixed> $config
 * @property array<string, mixed>|null $page_rule
 * @property bool $enabled
 */
#[Fillable([
    'blog_id', 'scope', 'author_profile_id', 'article_id', 'provider', 'config',
    'placement', 'consent_category', 'page_rule', 'enabled', 'created_by',
])]
class Integration extends Model
{
    /** @use HasFactory<IntegrationFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => IntegrationScope::class,
            'provider' => IntegrationProvider::class,
            'placement' => IntegrationPlacement::class,
            'consent_category' => ConsentCategory::class,
            'config' => 'array',
            'page_rule' => 'array',
            'enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Blog, $this>
     */
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * @return BelongsTo<AuthorProfile, $this>
     */
    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
