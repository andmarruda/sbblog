<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\Comments;
use App\Enums\CommentStatus;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Comment;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    public function index(Request $request, Workspace $workspace, Blog $blog): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in([...array_column(CommentStatus::cases(), 'value'), 'trash'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? CommentStatus::Pending->value;

        $comments = Comment::query()
            ->whereBelongsTo($blog)
            ->when($status === 'trash', fn ($query) => $query->onlyTrashed(), fn ($query) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(fn ($q) => $q
                ->where('body', 'ilike', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('author_name', 'ilike', '%'.addcslashes($search, '%_\\').'%')))
            ->with(['article.currentRevision:id,title', 'parent:id,author_name'])
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Comment $comment) => [
                'id' => $comment->id,
                'author_name' => $comment->author_name,
                'body' => $comment->body,
                'status' => $comment->status->value,
                'article' => ['id' => $comment->article_id, 'title' => $comment->article?->currentRevision->title ?? ''],
                'reply_to' => $comment->parent?->author_name,
                'created_at' => $comment->created_at?->toIso8601String(),
                'deleted_at' => $comment->deleted_at?->toIso8601String(),
            ]);

        $counts = Comment::query()->whereBelongsTo($blog)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('content/comments', [
            'comments' => $comments,
            'query' => [...$request->only('search'), 'status' => $status],
            'counts' => $counts,
        ]);
    }

    public function moderate(Request $request, Workspace $workspace, Blog $blog, Comment $comment, Comments $comments): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::enum(CommentStatus::class)->except([CommentStatus::Pending])]]);

        $comments->moderate($comment, CommentStatus::from($validated['status']), $this->user($request));

        return back();
    }

    public function bulk(Request $request, Workspace $workspace, Blog $blog, Comments $comments): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approved', 'rejected', 'spam', 'delete', 'restore'])],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['uuid'],
        ]);
        $user = $this->user($request);
        $action = $request->string('action')->toString();

        Comment::withTrashed()->whereBelongsTo($blog)->whereIn('id', $validated['ids'])->each(function (Comment $comment) use ($action, $user, $comments) {
            match ($action) {
                'delete' => $comment->trashed() ? null : $comments->delete($comment, $user),
                'restore' => $comment->trashed() ? $comments->restore($comment, $user) : null,
                default => $comment->trashed() ? null : $comments->moderate($comment, CommentStatus::from($action), $user),
            };
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comments updated.')]);

        return back();
    }

    public function reply(Request $request, Workspace $workspace, Blog $blog, Comment $comment, Comments $comments): RedirectResponse
    {
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        if (! $comment->is_public) {
            $comments->moderate($comment, CommentStatus::Approved, $this->user($request));
        }

        $comments->reply($comment->refresh(), $this->user($request), $validated['body']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reply published.')]);

        return back();
    }

    public function destroy(Request $request, Workspace $workspace, Blog $blog, Comment $comment, Comments $comments): RedirectResponse
    {
        $comments->delete($comment, $this->user($request));

        return back();
    }

    public function restore(Request $request, Workspace $workspace, Blog $blog, Comment $comment, Comments $comments): RedirectResponse
    {
        $comments->restore($comment, $this->user($request));

        return back();
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
