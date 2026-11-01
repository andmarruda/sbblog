<?php

namespace App\Models;

use Database\Factories\SocialNetworkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'name', 'icon'])]
#[WithoutTimestamps]
class SocialNetwork extends Model
{
    /** @use HasFactory<SocialNetworkFactory> */
    use HasFactory, HasUuids;

    /**
     * @return HasMany<SocialLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(SocialLink::class);
    }
}
