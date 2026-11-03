<?php

namespace App\Publishing\Payloads;

use App\Models\Comment;

class CommentPayload
{
    /**
     * Public fields only: no email or IP hashes.
     *
     * @return array{id: string, article_id: string, parent_id: string|null, author_name: string, body: string, created_at: string|null}
     */
    public static function make(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'article_id' => $comment->article_id,
            'parent_id' => $comment->parent_id,
            'author_name' => $comment->author_name,
            'body' => $comment->body,
            'created_at' => $comment->created_at?->toIso8601String(),
        ];
    }
}
