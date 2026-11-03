<?php

namespace App\Http\Controllers\Api;

use App\Actions\Content\Comments;
use App\Enums\ArticleStatus;
use App\Enums\CommentPolicy;
use App\Enums\CommentStatus;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Frontend;
use App\Support\VisitorHash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/**
 * Frontends are read-only, so they POST readers' comments here (docs/v2/events.md).
 */
class CommentController extends Controller
{
    public function store(Request $request, Comments $comments): JsonResponse
    {
        /** @var Frontend $frontend */
        $frontend = $request->attributes->get('frontend');
        $blog = $frontend->blog;

        if ($blog === null || $blog->comment_policy === CommentPolicy::Disabled) {
            return response()->json(['message' => 'Comments are disabled on this blog.'], 403);
        }

        $validated = $request->validate([
            'article_id' => ['required', 'uuid', Rule::exists('articles', 'id')
                ->where('blog_id', $blog->id)
                ->where('status', ArticleStatus::Published->value)
                ->whereNull('deleted_at')],
            'parent_id' => ['nullable', 'uuid', Rule::exists('comments', 'id')
                ->where('article_id', $request->input('article_id'))
                ->where('is_public', true)
                ->whereNull('deleted_at')],
            'author_name' => ['required', 'string', 'max:80'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            // Honeypot: real readers never fill it.
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        // Frontends render server-side, so the reader's IP comes in a header from them.
        $ipHash = VisitorHash::ip($request->header('X-SBBlog-Client-IP') ?: $request->ip());
        $key = 'comment|'.$blog->id.'|'.$ipHash;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many comments. Try again later.'], 429);
        }

        RateLimiter::hit($key, 60);

        $comment = Comment::query()->create([
            'blog_id' => $blog->id,
            'article_id' => $validated['article_id'],
            'parent_id' => $validated['parent_id'] ?? null,
            'author_name' => trim($validated['author_name']),
            'author_email_hash' => VisitorHash::email($validated['author_email'] ?? null),
            'body' => trim($validated['body']),
            'status' => CommentStatus::Pending,
            'is_public' => false,
            'ip_hash' => $ipHash,
        ]);

        if ($blog->comment_policy === CommentPolicy::Open) {
            $comment = $comments->moderate($comment, CommentStatus::Approved, null);
        }

        return response()->json(['id' => $comment->id, 'status' => $comment->status->value], 201);
    }
}
