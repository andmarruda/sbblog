<?php

namespace App\Models;

use App\Enums\TransportDriver;
use Carbon\CarbonImmutable;
use Database\Factories\EventTransportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property TransportDriver $driver
 * @property array<string, mixed>|null $config
 * @property bool $enabled
 * @property int $last_dispatched_sequence
 * @property CarbonImmutable|null $last_error_at
 */
#[Fillable([
    'blog_id', 'driver', 'config', 'enabled', 'last_dispatched_sequence', 'last_error_at', 'last_error',
])]
#[Hidden(['config'])]
class EventTransport extends Model
{
    /** @use HasFactory<EventTransportFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'driver' => TransportDriver::class,
            'config' => 'encrypted:array',
            'enabled' => 'boolean',
            'last_dispatched_sequence' => 'integer',
            'last_error_at' => 'datetime',
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
