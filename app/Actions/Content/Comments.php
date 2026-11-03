<?php

namespace App\Actions\Content;

use App\Enums\CommentStatus;
use App\Models\Blog;
use App\Models\Comment;
use App\Models\User;
use App\Publishing\EventRecorder;
use App\Publishing\Events\CommentApproved;
use App\Publishing\Events\CommentRemoved;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Comment moderation. Only approved comments are public; every change in visibility is recorded
 * in the blog's event log.
 */
class Comments
{
    public function __construct(
        private readonly EventRecorder $events,
        private readonly AuditLogger $audit,
    ) {}

    public function moderate(Comment $comment, CommentStatus $status, ?User $moderator): Comment
    {
        return DB::transaction(function () use ($comment, $status, $moderator) {
            $comment = Comment::query()->lockForUpdate()->findOrFail($comment->id);
            $wasPublic = $comment->is_public;

            $comment->forceFill([
                'status' => $status,
                'is_public' => $status === CommentStatus::Approved,
                'moderated_by' => $moderator?->id,
                'moderated_at' => now(),
            ])->save();

            $this->emit($comment, $wasPublic);

            if ($moderator !== null) {
                $this->audit->record('comment.'.$status->value, $comment, blog: $this->blog($comment), actor: $moderator);
            }

            return $comment;
        });
    }

    public function delete(Comment $comment, User $moderator): void
    {
        DB::transaction(function () use ($comment, $moderator) {
            $wasPublic = $comment->is_public;
            $comment->forceFill(['is_public' => false])->save();
            $comment->delete();

            if ($wasPublic) {
                $this->events->record($this->blog($comment), new CommentRemoved($comment));
            }

            $this->audit->record('comment.deleted', $comment, blog: $this->blog($comment), actor: $moderator);
        });
    }

    public function restore(Comment $comment, User $moderator): void
    {
        DB::transaction(function () use ($comment, $moderator) {
            $comment->restore();
            $comment->forceFill(['is_public' => $comment->status === CommentStatus::Approved])->save();
            $this->emit($comment, false);
            $this->audit->record('comment.restored', $comment, blog: $this->blog($comment), actor: $moderator);
        });
    }

    /**
     * Answer as the blog: published at once.
     */
    public function reply(Comment $parent, User $moderator, string $body): Comment
    {
        return DB::transaction(function () use ($parent, $moderator, $body) {
            $blog = $this->blog($parent);

            $reply = Comment::query()->create([
                'blog_id' => $blog->id,
                'article_id' => $parent->article_id,
                'parent_id' => $parent->id,
                'author_name' => $blog->name,
                'body' => $body,
                'status' => CommentStatus::Approved,
                'is_public' => true,
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
            ]);

            $this->events->record($blog, new CommentApproved($reply));

            return $reply;
        });
    }

    private function emit(Comment $comment, bool $wasPublic): void
    {
        if ($comment->is_public && ! $wasPublic) {
            $this->events->record($this->blog($comment), new CommentApproved($comment));
        } elseif (! $comment->is_public && $wasPublic) {
            $this->events->record($this->blog($comment), new CommentRemoved($comment));
        }
    }

    private function blog(Comment $comment): Blog
    {
        /** @var Blog */
        return $comment->blog()->firstOrFail();
    }
}
