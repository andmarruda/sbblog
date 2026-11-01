<?php

namespace App\Models;

use Database\Factories\SocialLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['blog_id', 'author_profile_id', 'social_network_id', 'url'])]
class SocialLink extends Model
{
    /** @use HasFactory<SocialLinkFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Blog, $this>
     */
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * Null for blog-level links.
     *
     * @return BelongsTo<AuthorProfile, $this>
     */
    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }

    /**
     * @return BelongsTo<SocialNetwork, $this>
     */
    public function socialNetwork(): BelongsTo
    {
        return $this->belongsTo(SocialNetwork::class);
    }
}
