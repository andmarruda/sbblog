<?php

namespace App\Models;

use Database\Factories\ArticleRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'article_id', 'number', 'title', 'description', 'body', 'cover_media_id',
    'created_by_user_id', 'created_by_app_installation_id',
])]
class ArticleRevision extends Model
{
    /** @use HasFactory<ArticleRevisionFactory> */
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        // Revisions are immutable: an edit is a new revision.
        static::updating(function () {
            throw new LogicException('Article revisions are immutable; create a new revision instead.');
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
            'number' => 'integer',
            'body' => 'array',
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
     * @return BelongsTo<Media, $this>
     */
    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<AppInstallation, $this>
     */
    public function createdByAppInstallation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class, 'created_by_app_installation_id');
    }
}
