<?php

namespace App\Publishing\Events;

use App\Models\Article;

/**
 * The article left the public site (unpublished, archived, scheduled again or deleted).
 */
final class ArticleUnpublished extends PublishableEvent
{
    public function __construct(private readonly Article $article) {}

    public static function type(): string
    {
        return 'article.unpublished';
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
        return ['id' => $this->article->id, 'translation_group_id' => $this->article->translation_group_id];
    }
}
