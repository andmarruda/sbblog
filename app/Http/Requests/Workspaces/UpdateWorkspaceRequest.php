<?php

namespace App\Http\Requests\Workspaces;

use App\Enums\TwoFactorPolicy;
use App\Models\Workspace;
use App\Rules\WorkspaceSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateWorkspaceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::lower(trim((string) $this->input('slug')))]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => WorkspaceSlug::rules($workspace),
            'two_factor_policy' => ['required', Rule::enum(TwoFactorPolicy::class)],
        ];
    }
}
