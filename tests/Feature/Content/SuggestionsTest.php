<?php

namespace Tests\Feature\Content;

use App\Actions\Content\ArticleEditor;
use App\Actions\Content\Suggestions;
use App\Enums\SuggestionStatus;
use App\Enums\WorkspaceRole;
use App\Models\ArticleSuggestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class SuggestionsTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    /**
     * @return array<string, mixed>
     */
    private function content(string $text): array
    {
        return [
            'title' => 'Proposed',
            'description' => null,
            'body' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]]]],
            'cover_media_id' => null,
        ];
    }

    public function test_reviewer_suggests_and_editor_accepts(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $reviewer = $this->member($workspace, WorkspaceRole::Member, $blog, 'reviewer');
        $article = app(ArticleEditor::class)->create($blog, $owner, 'Original');
        $base = "/{$workspace->slug}/{$blog->slug}/articles/{$article->id}";

        $this->actingAs($reviewer)->put("{$base}/draft", $this->content('nope'))->assertForbidden();
        $this->actingAs($reviewer)->post("{$base}/suggestions", [...$this->content('better'), 'note' => 'Fix typo'])->assertRedirect();

        $suggestion = ArticleSuggestion::query()->sole();
        $this->assertSame($article->current_revision_id, $suggestion->base_revision_id);
        $this->assertSame($article->current_revision_id, $article->refresh()->current_revision_id, 'Suggestions are not current.');

        $this->actingAs($reviewer)->post("{$base}/suggestions/{$suggestion->id}/comments", ['body' => 'See paragraph 1', 'quote' => 'better'])
            ->assertSessionHasNoErrors();
        $this->actingAs($owner)->get("{$base}/suggestions/{$suggestion->id}")
            ->assertInertia(fn ($page) => $page->has('comments', 1)->where('comments.0.quote', 'better')->where('suggestion.is_outdated', false));

        $this->actingAs($owner)->post("{$base}/suggestions/{$suggestion->id}/accept")->assertRedirect();

        $this->assertSame(SuggestionStatus::Accepted, $suggestion->refresh()->status);
        $this->assertSame($suggestion->proposed_revision_id, $article->refresh()->current_revision_id);
    }

    public function test_outdated_suggestions_are_superseded(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $article = app(ArticleEditor::class)->create($blog, $owner, 'Original');
        $base = "/{$workspace->slug}/{$blog->slug}/articles/{$article->id}";

        $this->actingAs($owner)->post("{$base}/suggestions", $this->content('suggested'));
        $this->actingAs($owner)->put("{$base}/draft", $this->content('edited meanwhile'));
        $suggestion = ArticleSuggestion::query()->sole();

        $this->actingAs($owner)->post("{$base}/suggestions/{$suggestion->id}/accept")->assertSessionHasErrors('suggestion');

        $this->assertSame(SuggestionStatus::Superseded, $suggestion->refresh()->status);
    }

    public function test_only_reviewers_review_and_only_once(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $author = $this->member($workspace, WorkspaceRole::Member, $blog, 'author');
        $article = app(ArticleEditor::class)->create($blog, $owner, 'Original');
        $base = "/{$workspace->slug}/{$blog->slug}/articles/{$article->id}";
        $this->actingAs($author)->post("{$base}/suggestions", $this->content('x'));
        $suggestion = ArticleSuggestion::query()->sole();

        $this->actingAs($author)->post("{$base}/suggestions/{$suggestion->id}/accept")->assertForbidden();
        $this->actingAs($owner)->post("{$base}/suggestions/{$suggestion->id}/reject", ['note' => 'No'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("{$base}/suggestions/{$suggestion->id}/accept")->assertSessionHasErrors('suggestion');

        $this->assertSame(SuggestionStatus::Rejected, $suggestion->refresh()->status);
    }

    public function test_suggestions_of_other_articles_are_not_found(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $article = app(ArticleEditor::class)->create($blog, $owner, 'A');
        $other = app(ArticleEditor::class)->create($blog, $owner, 'B');
        $suggestion = app(Suggestions::class)->create($other, $owner, $this->content('x'));

        $this->actingAs($owner)->get("/{$workspace->slug}/{$blog->slug}/articles/{$article->id}/suggestions/{$suggestion->id}")->assertNotFound();
    }
}
