<?php

namespace App\Publishing\Events;

use App\Models\Comment;

/**
 * A public comment was hidden, rejected, marked as spam or deleted.
 */
final class CommentRemoved extends PublishableEvent
{
    public function __construct(private readonly Comment $comment) {}

    public static function type(): string
    {
        return 'comment.removed';
    }

    public function aggregateType(): string
    {
        return 'comment';
    }

    public function aggregateId(): string
    {
        return $this->comment->id;
    }

    public function payload(): array
    {
        return ['id' => $this->comment->id, 'article_id' => $this->comment->article_id];
    }
}
