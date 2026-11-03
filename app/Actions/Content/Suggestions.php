<?php

namespace App\Actions\Content;

use App\Enums\SuggestionSource;
use App\Enums\SuggestionStatus;
use App\Models\Article;
use App\Models\ArticleSuggestion;
use App\Models\SuggestionComment;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Proposed changes. A suggestion is a revision that is not current; accepting it makes it the
 * current draft, which still has to be published.
 */
class Suggestions
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{title: string, description: string|null, body: array<string, mixed>, cover_media_id: string|null}  $content
     */
    public function create(Article $article, User $user, array $content, ?string $note = null): ArticleSuggestion
    {
        return DB::transaction(function () use ($article, $user, $content, $note) {
            $article = Article::query()->lockForUpdate()->findOrFail($article->id);

            $proposed = $article->revisions()->create([
                ...$content,
                'number' => (int) $article->revisions()->max('number') + 1,
                'created_by_user_id' => $user->id,
            ]);

            $suggestion = ArticleSuggestion::query()->create([
                'article_id' => $article->id,
                'base_revision_id' => $article->current_revision_id,
                'proposed_revision_id' => $proposed->id,
                'status' => SuggestionStatus::Pending,
                'source' => SuggestionSource::Human,
                'requested_by_user_id' => $user->id,
                'note' => $note,
            ]);

            $this->audit->record('suggestion.created', $suggestion, blog: $article->blog, actor: $user);

            return $suggestion;
        });
    }

    public function accept(ArticleSuggestion $suggestion, User $reviewer): void
    {
        $outdated = DB::transaction(function () use ($suggestion, $reviewer) {
            $suggestion = ArticleSuggestion::query()->lockForUpdate()->findOrFail($suggestion->id);
            $article = Article::query()->lockForUpdate()->findOrFail($suggestion->article_id);

            $this->ensurePending($suggestion);

            if ($suggestion->base_revision_id !== $article->current_revision_id) {
                // Committed before reporting the error, so the outdated mark sticks.
                $suggestion->update(['status' => SuggestionStatus::Superseded, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);

                return true;
            }

            $article->forceFill(['current_revision_id' => $suggestion->proposed_revision_id])->save();
            $suggestion->update(['status' => SuggestionStatus::Accepted, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);

            $this->audit->record('suggestion.accepted', $suggestion, blog: $article->blog, actor: $reviewer);

            return false;
        });

        if ($outdated) {
            throw ValidationException::withMessages([
                'suggestion' => __('The article changed after this suggestion was made, so it was marked as outdated.'),
            ]);
        }
    }

    public function reject(ArticleSuggestion $suggestion, User $reviewer, ?string $note = null): void
    {
        $this->ensurePending($suggestion);

        $suggestion->update([
            'status' => SuggestionStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'note' => $note ?? $suggestion->note,
        ]);

        $this->audit->record('suggestion.rejected', $suggestion, blog: $suggestion->article?->blog, actor: $reviewer);
    }

    /**
     * @param  array{quote?: string|null}|null  $anchor
     */
    public function comment(ArticleSuggestion $suggestion, User $user, string $body, ?array $anchor = null): SuggestionComment
    {
        return $suggestion->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
            'anchor' => $anchor === null || ($anchor['quote'] ?? '') === '' ? null : ['quote' => $anchor['quote']],
        ]);
    }

    private function ensurePending(ArticleSuggestion $suggestion): void
    {
        if ($suggestion->status !== SuggestionStatus::Pending) {
            throw ValidationException::withMessages(['suggestion' => __('This suggestion was already reviewed.')]);
        }
    }
}
