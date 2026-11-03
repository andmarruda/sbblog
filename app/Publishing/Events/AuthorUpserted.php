<?php

namespace App\Publishing\Events;

use App\Models\AuthorProfile;
use App\Publishing\Payloads\AuthorPayload;

final class AuthorUpserted extends PublishableEvent
{
    public function __construct(private readonly AuthorProfile $author) {}

    public static function type(): string
    {
        return 'author.upserted';
    }

    public function aggregateType(): string
    {
        return 'author_profile';
    }

    public function aggregateId(): string
    {
        return $this->author->id;
    }

    public function payload(): array
    {
        return ['author' => AuthorPayload::make($this->author)];
    }
}
