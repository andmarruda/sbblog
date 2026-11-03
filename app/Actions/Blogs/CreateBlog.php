<?php

namespace App\Actions\Blogs;

use App\Enums\CommentPolicy;
use App\Models\Blog;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AuditLogger;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;

class CreateBlog
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Workspace $workspace, User $creator, string $name, string $languageId, ?string $slug = null): Blog
    {
        return DB::transaction(function () use ($workspace, $creator, $name, $languageId, $slug) {
            $blog = $workspace->blogs()->create([
                'name' => $name,
                'slug' => $slug ?? self::suggestSlug($workspace, $name),
                'title' => $name,
                'default_language_id' => $languageId,
                'comment_policy' => CommentPolicy::Moderated,
                'autoconvert_webp' => true,
            ]);

            $blog->languages()->attach($languageId);

            $this->audit->record('blog.created', $blog, blog: $blog, actor: $creator);

            return $blog;
        });
    }

    public static function suggestSlug(Workspace $workspace, string $name): string
    {
        return Slug::unique(
            $name,
            fn (string $slug) => $workspace->blogs()->withTrashed()->where('slug', $slug)->exists(),
        );
    }
}
