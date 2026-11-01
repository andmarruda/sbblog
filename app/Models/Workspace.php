<?php

namespace App\Models;

use App\Enums\TwoFactorPolicy;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'owner_user_id', 'two_factor_policy', 'plan'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'two_factor_policy' => TwoFactorPolicy::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return BelongsToMany<User, $this, WorkspaceMember>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->using(WorkspaceMember::class)
            ->withPivot(['workspace_role', 'joined_at']);
    }

    /**
     * @return HasMany<Blog, $this>
     */
    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    /**
     * Custom roles; built-in roles have no workspace.
     *
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * @return HasMany<App, $this>
     */
    public function apps(): HasMany
    {
        return $this->hasMany(App::class);
    }

    /**
     * @return HasMany<ThemeLicense, $this>
     */
    public function themeLicenses(): HasMany
    {
        return $this->hasMany(ThemeLicense::class);
    }
}
