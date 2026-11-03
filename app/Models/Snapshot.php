<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $up_to_sequence
 * @property int $size_bytes
 * @property CarbonImmutable|null $created_at
 */
#[Fillable(['blog_id', 'up_to_sequence', 'storage_path', 'size_bytes'])]
class Snapshot extends Model
{
    /** @use HasFactory<SnapshotFactory> */
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'up_to_sequence' => 'integer',
            'size_bytes' => 'integer',
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
