<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table('workspace_members', timestamps: false)]
class WorkspaceMember extends Pivot
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'workspace_role' => WorkspaceRole::class,
            'joined_at' => 'datetime',
        ];
    }
}
