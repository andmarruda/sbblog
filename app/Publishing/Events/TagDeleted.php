<?php

namespace App\Publishing\Events;

final class TagDeleted extends PublishableEvent
{
    public function __construct(private readonly string $tagId) {}

    public static function type(): string
    {
        return 'tag.deleted';
    }

    public function aggregateType(): string
    {
        return 'tag';
    }

    public function aggregateId(): string
    {
        return $this->tagId;
    }

    public function payload(): array
    {
        return ['id' => $this->tagId];
    }
}
