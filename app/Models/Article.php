<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property ArticleStatus $status
 */
#[Fillable([
    'blog_id', 'author_profile_id', 'category_id', 'language_id', 'translation_group_id',
    'slug', 'status', 'current_revision_id', 'published_revision_id', 'bgcolor',
    'scheduled_at', 'published_at', 'created_by',
])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected static function booted(): void
    {
        // A new article starts its own translation group unless it joins an existing one.
        static::creating(function (self $article) {
            $article->translation_group_id ??= (string) Str::uuid7();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
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
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ArticleRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->orderBy('number');
    }

    /**
     * Latest draft.
     *
     * @return BelongsTo<ArticleRevision, $this>
     */
    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(ArticleRevision::class, 'current_revision_id');
    }

    /**
     * Live content.
     *
     * @return BelongsTo<ArticleRevision, $this>
     */
    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(ArticleRevision::class, 'published_revision_id');
    }

    /**
     * @return HasMany<ArticleSuggestion, $this>
     */
    public function suggestions(): HasMany
    {
        return $this->hasMany(ArticleSuggestion::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * The same article in other languages.
     *
     * @return HasMany<self, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(self::class, 'translation_group_id', 'translation_group_id')
            ->whereKeyNot($this->getKey());
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<Integration, $this>
     */
    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published)->whereNotNull('published_revision_id');
    }
}
