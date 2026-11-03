<?php

namespace App\Models;

use Database\Factories\SuggestionCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $anchor
 */
#[Fillable(['suggestion_id', 'user_id', 'body', 'anchor'])]
class SuggestionComment extends Model
{
    /** @use HasFactory<SuggestionCommentFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anchor' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ArticleSuggestion, $this>
     */
    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(ArticleSuggestion::class, 'suggestion_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
