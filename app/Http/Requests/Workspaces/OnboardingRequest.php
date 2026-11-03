<?php

namespace App\Http\Requests\Workspaces;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OnboardingRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'workspace_name' => ['required', 'string', 'max:120'],
            'blog_name' => ['required', 'string', 'max:120'],
            'language_id' => ['required', 'uuid', Rule::exists(Language::class, 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'workspace_name' => __('workspace name'),
            'blog_name' => __('blog name'),
            'language_id' => __('language'),
        ];
    }
}
