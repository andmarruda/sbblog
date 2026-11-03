<?php

namespace App\Publishing\Events;

use App\Models\Blog;
use App\Publishing\Payloads\BlogPayload;

/**
 * Blog-level settings changed: name, languages, brand, social links…
 */
final class BlogUpdated extends PublishableEvent
{
    public function __construct(private readonly Blog $blog) {}

    public static function type(): string
    {
        return 'blog.updated';
    }

    public function aggregateType(): string
    {
        return 'blog';
    }

    public function aggregateId(): string
    {
        return $this->blog->id;
    }

    public function payload(): array
    {
        return ['blog' => BlogPayload::make($this->blog)];
    }
}
