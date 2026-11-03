<?php

namespace Tests\Concerns;

use App\Enums\WorkspaceRole;
use App\Models\Blog;
use App\Models\Language;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

trait BuildsTenants
{
    protected function seedReferenceData(): void
    {
        $this->seed([LanguageSeeder::class, PermissionSeeder::class, RoleSeeder::class]);
    }

    /**
     * An owner with a workspace and one blog.
     *
     * @return array{User, Workspace, Blog}
     */
    protected function tenant(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->for($owner, 'owner')->create();
        $blog = Blog::factory()->for($workspace)->create([
            'default_language_id' => Language::query()->where('code', 'pt-BR')->value('id') ?? Language::factory(),
        ]);

        return [$owner, $workspace, $blog];
    }

    /**
     * Add a user to the workspace, optionally with a built-in role on a blog.
     */
    protected function member(Workspace $workspace, WorkspaceRole $role = WorkspaceRole::Member, ?Blog $blog = null, ?string $blogRole = null): User
    {
        $user = User::factory()->create();
        $workspace->members()->attach($user->id, ['workspace_role' => $role]);

        if ($blog !== null && $blogRole !== null) {
            DB::table('blog_members')->insert([
                'blog_id' => $blog->id,
                'user_id' => $user->id,
                'role_id' => Role::query()->builtIn()->where('key', $blogRole)->value('id'),
                'created_at' => now(),
            ]);
        }

        return $user;
    }
}
