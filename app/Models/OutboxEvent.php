<?php

namespace App\Models;

use Database\Factories\OutboxEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'blog_id', 'sequence', 'type', 'aggregate_type', 'aggregate_id',
    'schema_version', 'payload', 'occurred_at',
])]
#[WithoutTimestamps]
class OutboxEvent extends Model
{
    /** @use HasFactory<OutboxEventFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // The log is append-only.
        static::updating(function () {
            throw new LogicException('Outbox events are append-only.');
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
            'sequence' => 'integer',
            'schema_version' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'datetime',
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
