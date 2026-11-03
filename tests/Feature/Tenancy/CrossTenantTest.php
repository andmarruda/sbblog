<?php

namespace Tests\Feature\Tenancy;

use App\Actions\Content\Suggestions;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Category;
use App\Models\EventTransport;
use App\Models\Frontend;
use App\Models\Integration;
use App\Models\Invitation;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

/**
 * Every route that takes an ID must refuse IDs from another blog, even for owners of both.
 */
class CrossTenantTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    public function test_ids_from_another_blog_are_not_found(): void
    {
        $this->seedReferenceData();
        [$owner, $workspace, $blog] = $this->tenant();
        // The same owner also owns the other blog, so only scoping can stop them.
        $other = Blog::factory()->for($workspace)->create();

        $article = Article::factory()->for($other)->create();
        $suggestion = app(Suggestions::class)->create($article, $owner, [
            'title' => 'x', 'description' => null, 'cover_media_id' => null, 'body' => ['type' => 'doc', 'content' => []],
        ]);
        $ids = [
            'article' => $article->id,
            'revision' => $article->current_revision_id,
            'suggestion' => $suggestion->id,
            'category' => Category::factory()->for($other)->create()->id,
            'tag' => Tag::factory()->for($other)->create()->id,
            'author' => AuthorProfile::factory()->for($other)->create()->id,
            'media' => Media::factory()->for($other)->create()->id,
            'integration' => Integration::factory()->for($other)->create()->id,
            'frontend' => Frontend::factory()->for($other)->create()->id,
            'transport' => EventTransport::factory()->for($other)->create()->id,
        ];

        $base = "/{$workspace->slug}/{$blog->slug}";
        $requests = [
            ['get', "articles/{$ids['article']}"],
            ['put', "articles/{$ids['article']}/draft"],
            ['post', "articles/{$ids['article']}/publish"],
            ['delete', "articles/{$ids['article']}"],
            ['post', "articles/{$ids['article']}/restore"],
            ['get', "articles/{$ids['article']}/revisions"],
            ['get', "articles/{$ids['article']}/suggestions/{$ids['suggestion']}"],
            ['patch', "categories/{$ids['category']}"],
            ['delete', "categories/{$ids['category']}"],
            ['post', "categories/{$ids['category']}/restore"],
            ['patch', "tags/{$ids['tag']}"],
            ['post', "tags/{$ids['tag']}/merge"],
            ['patch', "authors/{$ids['author']}"],
            ['delete', "authors/{$ids['author']}"],
            ['patch', "media/{$ids['media']}"],
            ['delete', "media/{$ids['media']}"],
            ['patch', "integrations/{$ids['integration']}"],
            ['delete', "integrations/{$ids['integration']}"],
            ['patch', "publishing/frontends/{$ids['frontend']}"],
            ['post', "publishing/frontends/{$ids['frontend']}/token"],
            ['delete', "publishing/transports/{$ids['transport']}"],
        ];

        foreach ($requests as [$method, $path]) {
            $this->actingAs($owner)->{$method}("{$base}/{$path}")->assertNotFound();
        }

        // A revision of an article in this blog, but the revision ID of the other blog's article.
        $own = Article::factory()->for($blog)->create();
        $this->actingAs($owner)->post("{$base}/articles/{$own->id}/revisions/{$ids['revision']}/restore")->assertNotFound();
    }

    public function test_workspace_routes_refuse_other_workspaces_members_and_invitations(): void
    {
        $this->seedReferenceData();
        [$owner, $workspace] = $this->tenant();
        [, $otherWorkspace] = $this->tenant();
        $stranger = $otherWorkspace->members()->first() ?? User::factory()->create();
        $invitation = Invitation::factory()->for($otherWorkspace)->create();

        $this->actingAs($owner)->patch("/{$workspace->slug}/settings/members/{$stranger->id}", ['workspace_role' => 'admin'])->assertNotFound();
        $this->actingAs($owner)->delete("/{$workspace->slug}/settings/invitations/{$invitation->id}")->assertNotFound();
        $this->actingAs($owner)->post("/{$workspace->slug}/settings/invitations/{$invitation->id}/resend")->assertNotFound();
    }
}
