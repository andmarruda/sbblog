<?php

namespace App\Rules;

use App\Models\Workspace;
use App\Support\Slug;
use Illuminate\Validation\Rule;

class WorkspaceSlug
{
    /**
     * @return list<mixed>
     */
    public static function rules(?Workspace $ignore = null): array
    {
        return [
            'required', 'string', 'min:2', 'max:63', 'regex:'.Slug::PATTERN,
            Rule::notIn(config('sbblog.reserved_slugs')),
            // Soft-deleted workspaces keep their slug, so URLs can't be reused by someone else.
            Rule::unique(Workspace::class, 'slug')->ignore($ignore?->id),
        ];
    }
}
