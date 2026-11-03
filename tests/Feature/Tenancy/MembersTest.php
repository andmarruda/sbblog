<?php

namespace Tests\Feature\Tenancy;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Notifications\WorkspaceInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class MembersTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_admins_invite_by_email(): void
    {
        Notification::fake();
        [$owner, $workspace, $blog] = $this->tenant();
        $author = Role::query()->builtIn()->where('key', 'author')->value('id');

        $this->actingAs($owner)->post("/{$workspace->slug}/settings/invitations", [
            'email' => 'New@Example.com',
            'workspace_role' => 'member',
            'blog_id' => $blog->id,
            'role_id' => $author,
        ])->assertSessionHasNoErrors();

        $invitation = Invitation::query()->sole();
        $this->assertSame('new@example.com', $invitation->email);
        $this->assertSame($author, $invitation->role_id);

        Notification::assertSentTo(
            new AnonymousNotifiable,
            WorkspaceInvitation::class,
            fn (WorkspaceInvitation $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'new@example.com'
                && Invitation::findByToken($notification->token)?->is($invitation),
        );
    }

    public function test_inviting_again_replaces_the_pending_invitation(): void
    {
        Notification::fake();
        [$owner, $workspace] = $this->tenant();

        foreach ([1, 2] as $attempt) {
            $this->actingAs($owner)->post("/{$workspace->slug}/settings/invitations", [
                'email' => 'new@example.com', 'workspace_role' => 'member',
            ]);
        }

        $this->assertSame(1, Invitation::query()->count());
    }

    public function test_existing_members_cannot_be_invited(): void
    {
        [$owner, $workspace] = $this->tenant();

        $this->actingAs($owner)->post("/{$workspace->slug}/settings/invitations", [
            'email' => strtoupper($owner->email), 'workspace_role' => 'member',
        ])->assertSessionHasErrors('email');
    }

    public function test_the_owner_role_cannot_be_granted_by_invitation(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();

        $this->actingAs($owner)->post("/{$workspace->slug}/settings/invitations", [
            'email' => 'x@example.com', 'workspace_role' => 'owner',
        ])->assertSessionHasErrors('workspace_role');

        $this->actingAs($owner)->post("/{$workspace->slug}/settings/invitations", [
            'email' => 'x@example.com', 'workspace_role' => 'member',
            'blog_id' => $blog->id, 'role_id' => Role::query()->builtIn()->where('key', 'owner')->value('id'),
        ])->assertSessionHasErrors('role_id');
    }

    public function test_invited_user_accepts_and_gets_access(): void
    {
        [, $workspace, $blog] = $this->tenant();
        $user = User::factory()->unverified()->create(['email' => 'new@example.com']);
        Invitation::factory()->for($workspace)->withToken('secret-token')->create([
            'email' => 'new@example.com',
            'blog_id' => $blog->id,
            'role_id' => Role::query()->builtIn()->where('key', 'editor')->value('id'),
        ]);

        $this->actingAs($user)->get('/invitations/secret-token')->assertOk();
        $this->actingAs($user)->post('/invitations/secret-token')
            ->assertRedirect("/{$workspace->slug}/{$blog->slug}");

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
        $this->assertNotNull(Invitation::query()->sole()->accepted_at);
        $this->actingAs($user)->get("/{$workspace->slug}/{$blog->slug}")->assertOk();
    }

    public function test_guests_are_sent_back_to_the_invitation_after_signing_up(): void
    {
        [, $workspace] = $this->tenant();
        Invitation::factory()->for($workspace)->withToken('secret-token')->create(['email' => 'new@example.com']);

        $this->get('/invitations/secret-token')->assertOk();
        $this->get('/register')->assertInertia(fn ($page) => $page->where('email', 'new@example.com'));

        $this->post('/register', [
            'name' => 'New', 'email' => 'new@example.com', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirect(url('/invitations/secret-token'));
    }

    public function test_invitation_for_another_email_is_refused(): void
    {
        [, $workspace] = $this->tenant();
        Invitation::factory()->for($workspace)->withToken('secret-token')->create(['email' => 'someone@example.com']);

        $this->actingAs(User::factory()->create())->post('/invitations/secret-token')
            ->assertSessionHasErrors('invitation');

        $this->assertNull(Invitation::query()->sole()->accepted_at);
    }

    public function test_expired_or_used_invitations_are_refused(): void
    {
        [, $workspace] = $this->tenant();
        $user = User::factory()->create(['email' => 'new@example.com']);
        Invitation::factory()->for($workspace)->withToken('expired')->expired()->create(['email' => 'new@example.com']);

        $this->actingAs($user)->post('/invitations/expired')->assertSessionHasErrors('invitation');
        $this->actingAs($user)->post('/invitations/unknown')->assertNotFound();
    }

    public function test_accepting_never_downgrades_an_admin(): void
    {
        [, $workspace] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);
        Invitation::factory()->for($workspace)->withToken('t')->create(['email' => $admin->email]);

        $this->actingAs($admin)->post('/invitations/t');

        $this->assertSame(WorkspaceRole::Admin, $workspace->members()->whereKey($admin->id)->first()?->pivot?->getAttribute('workspace_role'));
    }

    public function test_roles_can_change_but_not_the_owners(): void
    {
        [$owner, $workspace] = $this->tenant();
        $member = $this->member($workspace);

        $this->actingAs($owner)->patch("/{$workspace->slug}/settings/members/{$member->id}", ['workspace_role' => 'admin'])
            ->assertSessionHasNoErrors();
        $this->assertSame(WorkspaceRole::Admin, $workspace->members()->whereKey($member->id)->first()?->pivot?->getAttribute('workspace_role'));

        $this->actingAs($member)->patch("/{$workspace->slug}/settings/members/{$owner->id}", ['workspace_role' => 'member'])
            ->assertSessionHasErrors('member');
    }

    public function test_removing_a_member_drops_their_blog_access(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $author = $this->member($workspace, WorkspaceRole::Member, $blog, 'author');

        $this->actingAs($owner)->delete("/{$workspace->slug}/settings/members/{$author->id}")->assertSessionHasNoErrors();

        $this->assertFalse($workspace->members()->whereKey($author->id)->exists());
        $this->assertFalse(DB::table('blog_members')->where('user_id', $author->id)->exists());
    }

    public function test_strangers_cannot_be_targeted(): void
    {
        [$owner, $workspace] = $this->tenant();
        $stranger = User::factory()->create();

        $this->actingAs($owner)->delete("/{$workspace->slug}/settings/members/{$stranger->id}")->assertNotFound();
    }

    public function test_owner_transfers_ownership(): void
    {
        [$owner, $workspace] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);

        $this->actingAs($owner)->post("/{$workspace->slug}/settings/members/{$admin->id}/transfer-ownership", ['password' => 'password'])
            ->assertSessionHasNoErrors();

        $workspace->refresh();
        $this->assertSame($admin->id, $workspace->owner_user_id);
        $this->assertSame(WorkspaceRole::Admin, $workspace->members()->whereKey($owner->id)->first()?->pivot?->getAttribute('workspace_role'));
    }

    public function test_only_the_owner_transfers_ownership(): void
    {
        [, $workspace] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);
        $member = $this->member($workspace);

        $this->actingAs($admin)->post("/{$workspace->slug}/settings/members/{$member->id}/transfer-ownership", ['password' => 'password'])
            ->assertForbidden();
    }

    public function test_blog_roles_can_be_assigned_and_removed(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $member = $this->member($workspace);
        $editor = Role::query()->builtIn()->where('key', 'editor')->value('id');

        $this->actingAs($owner)->put("/{$workspace->slug}/{$blog->slug}/settings/members/{$member->id}", ['role_id' => $editor])
            ->assertSessionHasNoErrors();
        $this->actingAs($member)->get("/{$workspace->slug}/{$blog->slug}")->assertOk();

        $this->actingAs($owner)->delete("/{$workspace->slug}/{$blog->slug}/settings/members/{$member->id}");
        $this->actingAs($member)->get("/{$workspace->slug}/{$blog->slug}")->assertNotFound();
    }

    public function test_blog_roles_are_only_for_workspace_members(): void
    {
        [$owner, $workspace, $blog] = $this->tenant();
        $admin = $this->member($workspace, WorkspaceRole::Admin);
        $editor = Role::query()->builtIn()->where('key', 'editor')->value('id');

        $this->actingAs($owner)->put("/{$workspace->slug}/{$blog->slug}/settings/members/{$admin->id}", ['role_id' => $editor])
            ->assertStatus(422);
        $this->actingAs($owner)->put("/{$workspace->slug}/{$blog->slug}/settings/members/".User::factory()->create()->id, ['role_id' => $editor])
            ->assertNotFound();
    }
}
