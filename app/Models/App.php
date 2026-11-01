<?php

namespace App\Models;

use Database\Factories\AppFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id', 'name', 'slug', 'is_first_party', 'client_id', 'client_secret_hash',
    'redirect_uris', 'requested_scopes',
])]
#[Hidden(['client_secret_hash'])]
class App extends Model
{
    /** @use HasFactory<AppFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_first_party' => 'boolean',
            'redirect_uris' => 'array',
            'requested_scopes' => 'array',
        ];
    }

    /**
     * Null for marketplace and first-party apps.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<AppInstallation, $this>
     */
    public function installations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }
}
