<?php

namespace App\Models;

use Database\Factories\ArticleDailyStatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Composite key (article_id, day): write with upsert(), not save().
 */
#[Fillable([
    'article_id', 'day', 'blog_id', 'author_profile_id', 'views', 'unique_visitors', 'avg_duration_ms',
])]
#[Table(key: false, incrementing: false, timestamps: false)]
class ArticleDailyStat extends Model
{
    /** @use HasFactory<ArticleDailyStatFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day' => 'date',
            'views' => 'integer',
            'unique_visitors' => 'integer',
            'avg_duration_ms' => 'integer',
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
}
