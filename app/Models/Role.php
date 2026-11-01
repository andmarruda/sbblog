<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['workspace_id', 'key', 'name', 'is_system', 'requires_two_factor'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'requires_two_factor' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_key');
    }

    /**
     * Built-in roles, shared by every workspace.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function builtIn(Builder $query): void
    {
        $query->whereNull('workspace_id');
    }

    /**
     * Built-in roles plus the workspace's custom roles.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function availableTo(Builder $query, Workspace $workspace): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('workspace_id')->orWhere('workspace_id', $workspace->id));
    }
}
