<?php

namespace App\Models;

use Database\Factories\WebauthnCredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $credential_id Base64url, as the browser sends it.
 * @property string $public_key PEM.
 * @property Carbon|null $last_used_at
 */
#[Fillable(['user_id', 'name', 'credential_id', 'public_key', 'sign_count', 'last_used_at'])]
#[Hidden(['public_key'])]
class WebauthnCredential extends Model
{
    /** @use HasFactory<WebauthnCredentialFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sign_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
