<?php

namespace App\Publishing\Payloads;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Media;
use App\Models\Tag;
use App\Publishing\Body\HtmlRenderer;
use App\Publishing\Body\TiptapDocument;
use LogicException;

/**
 * The full snapshot of a live article: everything a frontend needs to render its page,
 * so no frontend ever calls back to the console.
 */
class ArticlePayload
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Article $article, ?ArticleRevision $revision = null): array
    {
        $article->loadMissing(['language', 'authorProfile', 'category', 'tags']);
        $revision ??= $article->publishedRevision ?? throw new LogicException('The article has no published revision.');
        $revision->loadMissing('coverMedia');

        /** @var array<string, mixed> $body */
        $body = $revision->body;

        $media = Media::query()
            ->whereIn('id', TiptapDocument::mediaIds($body))
            ->where('blog_id', $article->blog_id)
            ->get();

        $renderer = new HtmlRenderer($media->mapWithKeys(fn (Media $item) => [$item->id => [
            'url' => $item->url('webp'),
            'width' => $item->width,
            'height' => $item->height,
            'alt' => $item->alt_text,
        ]])->all());

        return [
            'id' => $article->id,
            'blog_id' => $article->blog_id,
            'translation_group_id' => $article->translation_group_id,
            'language' => $article->language?->code,
            'slug' => $article->slug,
            'title' => $revision->title,
            'description' => $revision->description,
            'excerpt' => TiptapDocument::excerpt($body),
            'body' => $body,
            'html' => $renderer->render($body),
            'cover' => MediaPayload::maybe($revision->coverMedia),
            'media' => $media->map(fn (Media $item) => MediaPayload::make($item))->values()->all(),
            'author' => $article->authorProfile === null ? null : AuthorPayload::make($article->authorProfile),
            'category' => $article->category === null ? null : TaxonomyPayload::category($article->category),
            'tags' => $article->tags->map(fn (Tag $tag) => TaxonomyPayload::tag($tag))->values()->all(),
            'bgcolor' => $article->bgcolor,
            'published_at' => $article->published_at?->toIso8601String(),
            'updated_at' => $revision->created_at?->toIso8601String(),
            'revision' => ['id' => $revision->id, 'number' => $revision->number],
        ];
    }
}
