<?php

namespace Tests\Feature\Content;

use App\Actions\Content\ArticleEditor;
use App\Enums\WorkspaceRole;
use App\Jobs\ProcessMedia;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Category;
use App\Models\Media;
use App\Models\OutboxEvent;
use App\Models\SocialNetwork;
use App\Models\Tag;
use Database\Seeders\SocialNetworkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class TaxonomyAndMediaTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    /**
     * A tiny real JPEG, so uploads work without GD.
     */
    private function jpeg(string $name): UploadedFile
    {
        $bytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');

        return UploadedFile::fake()->createWithContent($name, (string) $bytes);
    }

    public function test_categories_are_created_moved_deleted_and_restored(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $base = "/{$workspace->slug}/{$blog->slug}/categories";

        $this->actingAs($owner)->post($base, ['name' => 'News'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post($base, ['name' => 'Sports'])->assertSessionHasNoErrors();
        $news = Category::query()->where('slug', 'news')->sole();
        $sports = Category::query()->where('slug', 'sports')->sole();
        $this->actingAs($owner)->post($base, ['name' => 'Local', 'parent_id' => $news->id]);
        $local = Category::query()->where('slug', 'local')->sole();

        $this->actingAs($owner)->post("{$base}/{$sports->id}/move", ['direction' => 'up']);
        $this->assertLessThan($news->refresh()->position, $sports->refresh()->position);

        $this->actingAs($owner)->patch("{$base}/{$news->id}", ['name' => 'News', 'slug' => 'news', 'parent_id' => $local->id])
            ->assertSessionHasErrors('parent_id');

        $article = Article::factory()->for($blog)->create(['category_id' => $news->id]);
        app(ArticleEditor::class)->publish($article);

        $this->actingAs($owner)->delete("{$base}/{$news->id}")->assertSessionHasNoErrors();
        $this->assertSoftDeleted($news);
        $this->assertNull($article->refresh()->category_id);
        $this->assertNull($local->refresh()->parent_id);

        $types = OutboxEvent::query()->whereBelongsTo($blog)->orderBy('sequence')->pluck('type')->all();
        $this->assertContains('category.deleted', $types);
        $this->assertSame('article.published', end($types), 'The live article is re-published without its category.');

        $this->actingAs($owner)->post("{$base}/{$news->id}/restore")->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted($news);
    }

    public function test_tags_merge_and_delete(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $base = "/{$workspace->slug}/{$blog->slug}/tags";
        $php = Tag::factory()->for($blog)->create(['name' => 'PHP', 'slug' => 'php']);
        $laravel = Tag::factory()->for($blog)->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $article = Article::factory()->for($blog)->create();
        $article->tags()->attach($php);
        app(ArticleEditor::class)->publish($article);

        $this->actingAs($owner)->post("{$base}/{$php->id}/merge", ['target_id' => $laravel->id])->assertSessionHasNoErrors();

        $this->assertModelMissing($php);
        $this->assertSame([$laravel->id], $article->tags()->pluck('tags.id')->all());

        $this->actingAs($owner)->post($base, ['name' => 'Laravel'])->assertSessionHasErrors('slug');
        $this->actingAs($owner)->delete("{$base}/{$laravel->id}")->assertSessionHasNoErrors();
        $this->assertSame(0, $article->tags()->count());
    }

    public function test_authors_have_social_links_and_cannot_be_deleted_with_articles(): void
    {
        $this->seed(SocialNetworkSeeder::class);
        [$owner, $workspace, $blog] = $this->tenant();
        $base = "/{$workspace->slug}/{$blog->slug}/authors";
        $github = SocialNetwork::query()->where('key', 'github')->value('id');

        $this->actingAs($owner)->post($base, [
            'display_name' => 'Guest Writer',
            'social_links' => [$github => 'https://github.com/guest'],
        ])->assertSessionHasNoErrors();

        $author = AuthorProfile::query()->where('slug', 'guest-writer')->sole();
        $this->assertSame('https://github.com/guest', $author->socialLinks()->sole()->url);
        $event = OutboxEvent::query()->whereBelongsTo($blog)->where('type', 'author.upserted')->sole();
        $this->assertSame('github', $event->payload['author']['social_links'][0]['network']);

        Article::factory()->for($blog)->create(['author_profile_id' => $author->id]);
        $this->actingAs($owner)->delete("{$base}/{$author->id}")->assertSessionHasErrors('author');
    }

    public function test_media_upload_dedupes_and_guards_deletion(): void
    {
        Storage::fake('public');
        Queue::fake();
        [$owner, $workspace, $blog] = $this->tenant();
        $base = "/{$workspace->slug}/{$blog->slug}/media";
        $this->actingAs($owner)->postJson($base, ['file' => $this->jpeg('Sunset at the beach.jpg')])->assertCreated()->assertJsonPath('width', 1);
        $this->actingAs($owner)->postJson($base, ['file' => $this->jpeg('Sunset at the beach.jpg')])->assertCreated();

        $media = Media::query()->sole();
        $this->assertSame('Sunset at the beach', $media->alt_text);
        Storage::disk('public')->assertExists($media->path);
        Queue::assertPushed(ProcessMedia::class);

        $article = Article::factory()->for($blog)->create();
        $editor = app(ArticleEditor::class);
        $editor->saveDraft($article, $owner, [
            'title' => 'x', 'description' => null, 'cover_media_id' => null,
            'body' => ['type' => 'doc', 'content' => [['type' => 'image', 'attrs' => ['mediaId' => $media->id]]]],
        ]);

        $this->actingAs($owner)->delete("{$base}/{$media->id}")->assertSessionHasErrors('media');

        $editor->saveDraft($article, $owner, ['title' => 'x', 'description' => null, 'cover_media_id' => null, 'body' => ['type' => 'doc', 'content' => []]]);
        $this->actingAs($owner)->delete("{$base}/{$media->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_uploads_are_limited_to_images(): void
    {
        Storage::fake('public');
        [$owner, $workspace, $blog] = $this->tenant();

        $this->actingAs($owner)->postJson("/{$workspace->slug}/{$blog->slug}/media", [
            'file' => UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml'),
        ])->assertJsonValidationErrors('file');
    }

    public function test_only_uploaders_and_managers_edit_media(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $author = $this->member($workspace, WorkspaceRole::Member, $blog, 'author');
        $media = Media::factory()->for($blog)->create(['uploaded_by' => $owner->id]);
        $own = Media::factory()->for($blog)->create(['uploaded_by' => $author->id]);
        $base = "/{$workspace->slug}/{$blog->slug}/media";

        $this->actingAs($author)->patch("{$base}/{$media->id}", ['alt_text' => 'x'])->assertForbidden();
        $this->actingAs($author)->patch("{$base}/{$own->id}", ['alt_text' => 'Mine'])->assertSessionHasNoErrors();
        $this->assertSame('Mine', $own->refresh()->alt_text);
    }

    public function test_media_variants_are_generated_when_gd_is_available(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is not installed.');
        }

        Storage::fake('public');
        [$owner, $workspace, $blog] = $this->tenant();

        $this->actingAs($owner)->postJson("/{$workspace->slug}/{$blog->slug}/media", [
            'file' => UploadedFile::fake()->image('wide.jpg', 3000, 1500),
        ])->assertCreated();

        $media = Media::query()->sole();
        $this->assertArrayHasKey('webp', $media->variants ?? []);
        Storage::disk('public')->assertExists($media->variants['thumb']);
    }

    public function test_blog_social_links_emit_blog_updated(): void
    {
        $this->seed(SocialNetworkSeeder::class);
        [$owner, $workspace, $blog] = $this->tenant();
        $x = SocialNetwork::query()->where('key', 'x')->value('id');

        $this->actingAs($owner)->put("/{$workspace->slug}/{$blog->slug}/settings/social", ['links' => [$x => 'https://x.com/blog']])
            ->assertSessionHasNoErrors();

        $event = OutboxEvent::query()->whereBelongsTo($blog)->sole();
        $this->assertSame('https://x.com/blog', $event->payload['blog']['social_links'][0]['url']);
    }
}
