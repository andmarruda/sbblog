<?php

namespace App\Models;

use Database\Factories\PageViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Raw, month-partitioned and short-lived. Dashboards read ArticleDailyStat instead.
 *
 * @property int $id
 * @property string $blog_id
 * @property string|null $article_id
 * @property string|null $author_profile_id
 * @property string|null $frontend_id
 * @property string $visitor_hash
 * @property string|null $referrer_host
 * @property string|null $country
 * @property string|null $device
 * @property int|null $duration_ms
 * @property Carbon $viewed_at
 */
#[Fillable([
    'blog_id', 'article_id', 'author_profile_id', 'frontend_id', 'visitor_hash',
    'referrer_host', 'country', 'device', 'duration_ms', 'viewed_at',
])]
#[WithoutTimestamps]
class PageView extends Model
{
    /** @use HasFactory<PageViewFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_ms' => 'integer',
            'viewed_at' => 'datetime',
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
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<AuthorProfile, $this>
     */
    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }

    /**
     * @return BelongsTo<Frontend, $this>
     */
    public function frontend(): BelongsTo
    {
        return $this->belongsTo(Frontend::class);
    }
}
