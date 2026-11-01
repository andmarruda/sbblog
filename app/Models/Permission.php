<?php

namespace App\Models;

use App\Enums\PermissionScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['key', 'group', 'scope', 'requires_two_factor', 'description'])]
#[Table(key: 'key', keyType: 'string', incrementing: false, timestamps: false)]
class Permission extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => PermissionScope::class,
            'requires_two_factor' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_key', 'role_id');
    }
}
