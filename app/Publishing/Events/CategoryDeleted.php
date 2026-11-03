<?php

namespace App\Publishing\Events;

final class CategoryDeleted extends PublishableEvent
{
    public function __construct(private readonly string $categoryId) {}

    public static function type(): string
    {
        return 'category.deleted';
    }

    public function aggregateType(): string
    {
        return 'category';
    }

    public function aggregateId(): string
    {
        return $this->categoryId;
    }

    public function payload(): array
    {
        return ['id' => $this->categoryId];
    }
}
