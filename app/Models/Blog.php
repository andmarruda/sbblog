<?php

namespace App\Models;

use App\Enums\CommentPolicy;
use Database\Factories\BlogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CommentPolicy $comment_policy
 * @property bool $autoconvert_webp
 * @property int $event_sequence
 */
#[Fillable([
    'workspace_id', 'name', 'slug', 'canonical_url', 'title', 'slogan', 'description',
    'brand_media_id', 'default_language_id', 'comment_policy', 'autoconvert_webp',
])]
class Blog extends Model
{
    /** @use HasFactory<BlogFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'comment_policy' => CommentPolicy::class,
            'autoconvert_webp' => 'boolean',
            'event_sequence' => 'integer',
        ];
    }

    /**
     * Blogs that still run: neither the blog nor its workspace is deleted. Background work
     * (scheduling, delivery, snapshots, sync) only touches these.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereHas('workspace');
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function brandMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'brand_media_id');
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function defaultLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'default_language_id');
    }

    /**
     * @return BelongsToMany<Language, $this>
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'blog_languages');
    }

    /**
     * @return BelongsToMany<User, $this, BlogMember>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blog_members')
            ->using(BlogMember::class)
            ->withPivot(['role_id', 'invited_by']);
    }

    /**
     * @return HasMany<AuthorProfile, $this>
     */
    public function authorProfiles(): HasMany
    {
        return $this->hasMany(AuthorProfile::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * @return HasMany<Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * @return HasMany<Media, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    /**
     * Blog-level links only; author links live on the author profile.
     *
     * @return HasMany<SocialLink, $this>
     */
    public function socialLinks(): HasMany
    {
        return $this->hasMany(SocialLink::class)->whereNull('author_profile_id');
    }

    /**
     * @return HasMany<Integration, $this>
     */
    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    /**
     * @return HasMany<OutboxEvent, $this>
     */
    public function outboxEvents(): HasMany
    {
        return $this->hasMany(OutboxEvent::class);
    }

    /**
     * @return HasMany<EventTransport, $this>
     */
    public function eventTransports(): HasMany
    {
        return $this->hasMany(EventTransport::class);
    }

    /**
     * @return HasMany<Frontend, $this>
     */
    public function frontends(): HasMany
    {
        return $this->hasMany(Frontend::class);
    }

    /**
     * @return HasMany<Snapshot, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class);
    }

    /**
     * @return HasMany<AppInstallation, $this>
     */
    public function appInstallations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<BlogTheme, $this>
     */
    public function themes(): HasMany
    {
        return $this->hasMany(BlogTheme::class);
    }

    /**
     * @return HasOne<BlogTheme, $this>
     */
    public function activeTheme(): HasOne
    {
        return $this->hasOne(BlogTheme::class)->where('is_active', true);
    }
}
