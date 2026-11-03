<?php

namespace App\Publishing\Events;

use App\Models\Tag;
use App\Publishing\Payloads\TaxonomyPayload;

final class TagUpserted extends PublishableEvent
{
    public function __construct(private readonly Tag $tag) {}

    public static function type(): string
    {
        return 'tag.upserted';
    }

    public function aggregateType(): string
    {
        return 'tag';
    }

    public function aggregateId(): string
    {
        return $this->tag->id;
    }

    public function payload(): array
    {
        return ['tag' => TaxonomyPayload::tag($this->tag)];
    }
}
