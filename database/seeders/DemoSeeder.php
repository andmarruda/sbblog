<?php

namespace Database\Seeders;

use App\Enums\WorkspaceRole;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\BlogTheme;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Integration;
use App\Models\Language;
use App\Models\Role;
use App\Models\SocialLink;
use App\Models\SocialNetwork;
use App\Models\Tag;
use App\Models\Theme;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Seed a demo workspace with one blog and some content. Local use only.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'language_id' => Language::query()->where('code', 'pt-BR')->value('id'),
        ]);

        $workspace = Workspace::factory()->for($owner, 'owner')->create([
            'name' => 'Sysborg',
            'slug' => 'sysborg',
        ]);

        $blog = Blog::factory()->for($workspace)->create([
            'name' => 'SBBlog Demo',
            'slug' => 'demo',
            'default_language_id' => Language::query()->where('code', 'pt-BR')->value('id'),
        ]);
        $blog->languages()->syncWithoutDetaching(Language::query()->where('code', 'en-US')->pluck('id'));

        $blog->members()->attach($owner->id, [
            'role_id' => Role::query()->builtIn()->where('key', 'owner')->value('id'),
        ]);

        $editor = User::factory()->create(['email' => 'editor@example.com']);
        $workspace->members()->attach($editor->id, ['workspace_role' => WorkspaceRole::Member]);
        $blog->members()->attach($editor->id, [
            'role_id' => Role::query()->builtIn()->where('key', 'editor')->value('id'),
            'invited_by' => $owner->id,
        ]);

        $authors = collect([
            AuthorProfile::factory()->for($blog)->for($owner)->create(['display_name' => $owner->name, 'slug' => 'test-user']),
            AuthorProfile::factory()->for($blog)->for($editor)->create(),
            AuthorProfile::factory()->for($blog)->guest()->create(),
        ]);

        $categories = Category::factory()->for($blog)->count(4)->create();
        Category::factory()->for($blog)->count(2)->create(['parent_id' => $categories->first()?->id]);

        $tags = Tag::factory()->for($blog)->count(8)->create();

        $articles = Article::factory()
            ->count(12)
            ->published()
            ->for($blog)
            ->sequence(fn () => [
                'author_profile_id' => $authors->random()->id,
                'category_id' => $categories->random()->id,
            ])
            ->create();

        Article::factory()->count(3)->for($blog)->sequence(fn () => [
            'author_profile_id' => $authors->random()->id,
            'category_id' => $categories->random()->id,
        ])->create();

        $articles->each(function (Article $article) use ($tags) {
            $article->tags()->attach($tags->random(3)->pluck('id'));
            Comment::factory()->for($article)->count(2)->approved()->create(['blog_id' => $article->blog_id]);
        });

        foreach (['x', 'instagram', 'github'] as $network) {
            SocialLink::factory()->for($blog)->create([
                'social_network_id' => SocialNetwork::query()->where('key', $network)->value('id'),
            ]);
        }

        Integration::factory()->for($blog)->create();

        BlogTheme::factory()->for($blog)->active()->create([
            'theme_id' => Theme::query()->where('slug', 'default')->value('id'),
        ]);
    }
}
