<?php

namespace App\Publishing\Events;

final class AuthorDeleted extends PublishableEvent
{
    public function __construct(private readonly string $authorId) {}

    public static function type(): string
    {
        return 'author.deleted';
    }

    public function aggregateType(): string
    {
        return 'author_profile';
    }

    public function aggregateId(): string
    {
        return $this->authorId;
    }

    public function payload(): array
    {
        return ['id' => $this->authorId];
    }
}
