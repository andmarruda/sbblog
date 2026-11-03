<?php

namespace App\Models;

use App\Enums\StoreAdapter;
use Carbon\CarbonImmutable;
use Database\Factories\FrontendFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property StoreAdapter $store_adapter
 * @property int $last_acked_sequence
 * @property CarbonImmutable|null $last_sync_at
 */
#[Fillable([
    'blog_id', 'name', 'base_url', 'store_adapter', 'sync_token_hash',
    'last_acked_sequence', 'last_sync_at', 'core_version',
])]
#[Hidden(['sync_token_hash'])]
class Frontend extends Model
{
    /** @use HasFactory<FrontendFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'store_adapter' => StoreAdapter::class,
            'last_acked_sequence' => 'integer',
            'last_sync_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Blog, $this>
     */
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
