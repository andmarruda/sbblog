<?php

namespace App\Models;

use App\Enums\MediaDisk;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'blog_id', 'uploaded_by', 'disk', 'path', 'mime_type', 'size_bytes',
    'width', 'height', 'alt_text', 'variants', 'checksum',
])]
#[Table('media')]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'disk' => MediaDisk::class,
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
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
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
