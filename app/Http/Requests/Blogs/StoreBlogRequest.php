<?php

namespace App\Http\Requests\Blogs;

use App\Models\Language;
use App\Models\Workspace;
use App\Support\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreBlogRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug'));

        $this->merge(['slug' => $slug === '' ? null : Str::lower($slug)]);
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
            'slug' => [
                'nullable', 'string', 'min:2', 'max:63', 'regex:'.Slug::PATTERN,
                Rule::unique('blogs', 'slug')->where('workspace_id', $workspace->id),
                // "settings" is a sibling route under the workspace.
                Rule::notIn(['settings']),
            ],
            'language_id' => ['required', 'uuid', Rule::exists(Language::class, 'id')],
        ];
    }
}
