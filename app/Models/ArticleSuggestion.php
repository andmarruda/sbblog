<?php

namespace App\Models;

use App\Enums\SuggestionSource;
use App\Enums\SuggestionStatus;
use Database\Factories\ArticleSuggestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'article_id', 'base_revision_id', 'proposed_revision_id', 'status', 'source',
    'requested_by_user_id', 'requested_by_app_installation_id', 'reviewed_by', 'reviewed_at', 'note',
])]
class ArticleSuggestion extends Model
{
    /** @use HasFactory<ArticleSuggestionFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SuggestionStatus::class,
            'source' => SuggestionSource::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<ArticleRevision, $this>
     */
    public function baseRevision(): BelongsTo
    {
        return $this->belongsTo(ArticleRevision::class, 'base_revision_id');
    }

    /**
     * @return BelongsTo<ArticleRevision, $this>
     */
    public function proposedRevision(): BelongsTo
    {
        return $this->belongsTo(ArticleRevision::class, 'proposed_revision_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * @return BelongsTo<AppInstallation, $this>
     */
    public function requestedByAppInstallation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class, 'requested_by_app_installation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<SuggestionComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(SuggestionComment::class, 'suggestion_id');
    }
}
