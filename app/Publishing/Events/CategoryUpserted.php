<?php

namespace App\Publishing\Events;

use App\Models\Category;
use App\Publishing\Payloads\TaxonomyPayload;

final class CategoryUpserted extends PublishableEvent
{
    public function __construct(private readonly Category $category) {}

    public static function type(): string
    {
        return 'category.upserted';
    }

    public function aggregateType(): string
    {
        return 'category';
    }

    public function aggregateId(): string
    {
        return $this->category->id;
    }

    public function payload(): array
    {
        return ['category' => TaxonomyPayload::category($this->category)];
    }
}
