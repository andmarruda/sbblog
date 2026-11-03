<?php

namespace App\Http\Requests\Content;

use App\Models\Article;
use App\Models\Blog;
use App\Support\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateArticleDetailsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge(['slug' => Str::lower(trim((string) $this->input('slug')))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Blog $blog */
        $blog = $this->route('blog');
        /** @var Article $article */
        $article = $this->route('article');

        return [
            'slug' => [
                'sometimes', 'required', 'string', 'max:120', 'regex:'.Slug::PATTERN,
                Rule::unique('articles', 'slug')
                    ->where('blog_id', $blog->id)
                    ->where('language_id', $article->language_id)
                    ->ignore($article->id),
            ],
            'category_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('categories', 'id')->where('blog_id', $blog->id)->whereNull('deleted_at')],
            'author_profile_id' => ['sometimes', 'required', 'uuid', Rule::exists('author_profiles', 'id')->where('blog_id', $blog->id)],
            'bgcolor' => ['sometimes', 'nullable', 'hex_color'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['uuid', 'distinct', Rule::exists('tags', 'id')->where('blog_id', $blog->id)],
        ];
    }
}
