<?php

namespace App\Publishing\Events;

use App\Models\Article;
use App\Publishing\Payloads\ArticlePayload;

/**
 * A revision went live (first publish or an update). Frontends replace the whole article.
 */
final class ArticlePublished extends PublishableEvent
{
    public function __construct(private readonly Article $article) {}

    public static function type(): string
    {
        return 'article.published';
    }

    public function aggregateType(): string
    {
        return 'article';
    }

    public function aggregateId(): string
    {
        return $this->article->id;
    }

    public function payload(): array
    {
        return ['article' => ArticlePayload::make($this->article)];
    }
}
