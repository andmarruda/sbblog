<?php

namespace App\Publishing\Payloads;

use App\Models\Blog;
use App\Models\Language;
use App\Models\SocialLink;

class BlogPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Blog $blog): array
    {
        $blog->loadMissing(['defaultLanguage', 'languages', 'brandMedia', 'socialLinks.socialNetwork']);

        return [
            'id' => $blog->id,
            'name' => $blog->name,
            'title' => $blog->title,
            'slogan' => $blog->slogan,
            'description' => $blog->description,
            'canonical_url' => $blog->canonical_url,
            'default_language' => $blog->defaultLanguage?->code,
            'languages' => $blog->languages->map(fn (Language $language) => $language->code)->values()->all(),
            'comment_policy' => $blog->comment_policy->value,
            'brand' => MediaPayload::maybe($blog->brandMedia),
            'social_links' => $blog->socialLinks
                ->map(fn (SocialLink $link) => ['network' => $link->socialNetwork?->key, 'url' => $link->url])
                ->values()
                ->all(),
        ];
    }
}
