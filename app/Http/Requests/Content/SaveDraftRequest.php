<?php

namespace App\Http\Requests\Content;

use App\Models\Blog;
use App\Models\Media;
use App\Publishing\Body\TiptapDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * Editor content: title, description, cover and the Tiptap body.
 */
class SaveDraftRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Blog $blog */
        $blog = $this->route('blog');

        return [
            'title' => ['present', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'array'],
            'cover_media_id' => ['nullable', 'uuid', Rule::exists('media', 'id')->where('blog_id', $blog->id)],
            'autosave' => ['boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('body')) {
                return;
            }

            /** @var array<string, mixed> $body */
            $body = $this->input('body');

            try {
                TiptapDocument::validate($body);
            } catch (ValidationException $exception) {
                $validator->errors()->merge($exception->errors());

                return;
            }

            /** @var Blog $blog */
            $blog = $this->route('blog');
            $ids = TiptapDocument::mediaIds($body);

            if (count($ids) !== Media::query()->whereBelongsTo($blog)->whereIn('id', $ids)->count()) {
                $validator->errors()->add('body', __('The article uses an image that is not in this blog.'));
            }
        }];
    }

    /**
     * @return array{title: string, description: string|null, body: array<string, mixed>, cover_media_id: string|null}
     */
    public function content(): array
    {
        /** @var array<string, mixed> $body */
        $body = $this->input('body');

        return [
            'title' => (string) $this->input('title', ''),
            'description' => $this->filled('description') ? (string) $this->input('description') : null,
            'body' => $body,
            'cover_media_id' => $this->filled('cover_media_id') ? (string) $this->input('cover_media_id') : null,
        ];
    }
}
