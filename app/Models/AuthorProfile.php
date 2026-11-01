<?php

namespace App\Models;

use Database\Factories\AuthorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['blog_id', 'user_id', 'display_name', 'slug', 'bio', 'avatar_media_id'])]
class AuthorProfile extends Model
{
    /** @use HasFactory<AuthorProfileFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Blog, $this>
     */
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * Null for guest authors.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'avatar_media_id');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * @return HasMany<SocialLink, $this>
     */
    public function socialLinks(): HasMany
    {
        return $this->hasMany(SocialLink::class);
    }

    /**
     * @return HasMany<Integration, $this>
     */
    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }
}
