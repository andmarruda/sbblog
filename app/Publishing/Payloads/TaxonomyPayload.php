<?php

namespace App\Publishing\Payloads;

use App\Models\Category;
use App\Models\Tag;

class TaxonomyPayload
{
    /**
     * @return array{id: string, name: string, slug: string, parent_id: string|null, bgcolor: string|null, position: int}
     */
    public static function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'bgcolor' => $category->bgcolor,
            'position' => $category->position,
        ];
    }

    /**
     * @return array{id: string, name: string, slug: string}
     */
    public static function tag(Tag $tag): array
    {
        return ['id' => $tag->id, 'name' => $tag->name, 'slug' => $tag->slug];
    }
}
