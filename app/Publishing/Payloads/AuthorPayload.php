<?php

namespace App\Publishing\Payloads;

use App\Models\AuthorProfile;
use App\Models\SocialLink;

class AuthorPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function make(AuthorProfile $author): array
    {
        $author->loadMissing(['avatar', 'socialLinks.socialNetwork']);

        return [
            'id' => $author->id,
            'display_name' => $author->display_name,
            'slug' => $author->slug,
            'bio' => $author->bio,
            'avatar' => MediaPayload::maybe($author->avatar),
            'social_links' => $author->socialLinks
                ->map(fn (SocialLink $link) => ['network' => $link->socialNetwork?->key, 'url' => $link->url])
                ->values()
                ->all(),
        ];
    }
}
