<?php

namespace App\Http\Requests\Blogs;

use App\Enums\CommentPolicy;
use App\Models\Blog;
use App\Models\Language;
use App\Support\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBlogRequest extends FormRequest
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
        /** @var Blog $blog */
        $blog = $this->route('blog');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required', 'string', 'min:2', 'max:63', 'regex:'.Slug::PATTERN, Rule::notIn(['settings']),
                Rule::unique('blogs', 'slug')->where('workspace_id', $blog->workspace_id)->ignore($blog->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url:https,http', 'max:255'],
            'default_language_id' => ['required', 'uuid', Rule::exists(Language::class, 'id'), 'in_array:language_ids.*'],
            'language_ids' => ['required', 'array', 'min:1'],
            'language_ids.*' => ['uuid', 'distinct', Rule::exists(Language::class, 'id')],
            'comment_policy' => ['required', Rule::enum(CommentPolicy::class)],
            'autoconvert_webp' => ['boolean'],
            'brand_media_id' => ['nullable', 'uuid', Rule::exists('media', 'id')->where('blog_id', $blog->id)],
        ];
    }
}
