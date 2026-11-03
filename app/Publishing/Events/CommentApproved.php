<?php

namespace App\Publishing\Events;

use App\Models\Comment;
use App\Publishing\Payloads\CommentPayload;

final class CommentApproved extends PublishableEvent
{
    public function __construct(private readonly Comment $comment) {}

    public static function type(): string
    {
        return 'comment.approved';
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
        return ['comment' => CommentPayload::make($this->comment)];
    }
}
