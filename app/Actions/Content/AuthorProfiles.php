<?php

namespace App\Actions\Content;

use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\SocialLink;
use App\Models\User;
use App\Publishing\EventRecorder;
use App\Publishing\Events\AuthorDeleted;
use App\Publishing\Events\AuthorUpserted;
use App\Support\AuditLogger;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthorProfiles
{
    public function __construct(
        private readonly EventRecorder $events,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * The user's profile on this blog, created from their account the first time they write.
     */
    public function forUser(Blog $blog, User $user): AuthorProfile
    {
        $existing = AuthorProfile::query()->whereBelongsTo($blog)->whereBelongsTo($user)->first();

        return $existing ?? $this->save($blog, [
            'user_id' => $user->id,
            'display_name' => $user->name,
            'slug' => Slug::unique($user->name, fn (string $slug) => $blog->authorProfiles()->where('slug', $slug)->exists()),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>|null  $socialLinks  social_network_id => url; null leaves them as they are.
     */
    public function save(Blog $blog, array $data, ?AuthorProfile $author = null, ?array $socialLinks = null): AuthorProfile
    {
        return DB::transaction(function () use ($blog, $data, $author, $socialLinks) {
            $author ??= new AuthorProfile(['blog_id' => $blog->id]);
            $author->fill($data)->save();

            if ($socialLinks !== null) {
                $this->syncSocialLinks($blog, $author, $socialLinks);
            }

            $this->events->record($blog, new AuthorUpserted($author->unsetRelation('socialLinks')));
            $this->audit->record('author.saved', $author, ['name' => $author->display_name], blog: $blog);

            return $author;
        });
    }

    public function delete(Blog $blog, AuthorProfile $author): void
    {
        if ($author->articles()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'author' => __('Move this author\'s articles to someone else before deleting the profile.'),
            ]);
        }

        DB::transaction(function () use ($blog, $author) {
            $author->socialLinks()->delete();
            $author->delete();

            $this->events->record($blog, new AuthorDeleted($author->id));
            $this->audit->record('author.deleted', null, ['name' => $author->display_name], blog: $blog);
        });
    }

    /**
     * @param  array<string, string>  $links  social_network_id => url
     */
    private function syncSocialLinks(Blog $blog, ?AuthorProfile $author, array $links): void
    {
        $query = SocialLink::query()->whereBelongsTo($blog)->where('author_profile_id', $author?->id);

        $query->clone()->whereNotIn('social_network_id', array_keys($links))->delete();

        foreach ($links as $networkId => $url) {
            SocialLink::query()->updateOrCreate(
                ['blog_id' => $blog->id, 'author_profile_id' => $author?->id, 'social_network_id' => $networkId],
                ['url' => $url],
            );
        }
    }

    /**
     * Blog-level links (no author).
     *
     * @param  array<string, string>  $links
     */
    public function syncBlogLinks(Blog $blog, array $links): void
    {
        $this->syncSocialLinks($blog, null, $links);
    }
}
