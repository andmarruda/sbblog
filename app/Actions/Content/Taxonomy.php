<?php

namespace App\Actions\Content;

use App\Models\Article;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use App\Publishing\EventRecorder;
use App\Publishing\Events\CategoryDeleted;
use App\Publishing\Events\CategoryUpserted;
use App\Publishing\Events\TagDeleted;
use App\Publishing\Events\TagUpserted;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Categories and tags. Every change is mirrored in the blog's event log.
 */
class Taxonomy
{
    public function __construct(
        private readonly EventRecorder $events,
        private readonly RepublishArticles $republish,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{name: string, slug: string, parent_id?: string|null, bgcolor?: string|null}  $data
     */
    public function saveCategory(Blog $blog, array $data, ?Category $category = null): Category
    {
        return DB::transaction(function () use ($blog, $data, $category) {
            if ($category !== null && ($data['parent_id'] ?? null) !== null) {
                $this->ensureNotDescendant($category, (string) $data['parent_id']);
            }

            $category ??= new Category([
                'blog_id' => $blog->id,
                'position' => (int) $blog->categories()->where('parent_id', $data['parent_id'] ?? null)->max('position') + 1,
            ]);

            $category->fill($data)->save();
            $this->events->record($blog, new CategoryUpserted($category));
            $this->audit->record('category.saved', $category, ['name' => $category->name], blog: $blog);

            return $category;
        });
    }

    /**
     * Soft-delete; children move up to the deleted category's parent and articles lose it.
     */
    public function deleteCategory(Blog $blog, Category $category): void
    {
        DB::transaction(function () use ($blog, $category) {
            $affected = Article::query()->where('category_id', $category->id)->pluck('id');

            foreach ($category->children()->get() as $child) {
                $child->update(['parent_id' => $category->parent_id]);
                $this->events->record($blog, new CategoryUpserted($child));
            }

            Article::query()->where('category_id', $category->id)->update(['category_id' => null]);
            $category->delete();

            $this->events->record($blog, new CategoryDeleted($category->id));
            $this->republish->handle($blog, Article::query()->whereIn('id', $affected));
            $this->audit->record('category.deleted', $category, ['name' => $category->name], blog: $blog);
        });
    }

    public function restoreCategory(Blog $blog, Category $category): void
    {
        DB::transaction(function () use ($blog, $category) {
            $category->restore();

            if ($category->parent_id !== null && $category->parent()->doesntExist()) {
                $category->update(['parent_id' => null]);
            }

            $this->events->record($blog, new CategoryUpserted($category));
        });
    }

    /**
     * Swap the position with the previous or next sibling.
     */
    public function moveCategory(Blog $blog, Category $category, string $direction): void
    {
        DB::transaction(function () use ($blog, $category, $direction) {
            $siblings = $blog->categories()
                ->where('parent_id', $category->parent_id)
                ->orderBy('position')
                ->orderBy('name')
                ->lockForUpdate()
                ->get()
                ->values();

            $index = $siblings->search(fn (Category $sibling) => $sibling->id === $category->id);
            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! $siblings->has($target)) {
                return;
            }

            // Renumber so positions are distinct, then swap.
            $siblings->each(fn (Category $sibling, int $position) => $sibling->position = $position);
            [$siblings[$index]->position, $siblings[$target]->position] = [$siblings[$target]->position, $siblings[$index]->position];

            $siblings->each(function (Category $sibling) use ($blog) {
                if ($sibling->isDirty('position')) {
                    $sibling->save();
                    $this->events->record($blog, new CategoryUpserted($sibling));
                }
            });
        });
    }

    /**
     * @param  array{name: string, slug: string}  $data
     */
    public function saveTag(Blog $blog, array $data, ?Tag $tag = null): Tag
    {
        return DB::transaction(function () use ($blog, $data, $tag) {
            $tag ??= new Tag(['blog_id' => $blog->id]);
            $tag->fill($data)->save();

            $this->events->record($blog, new TagUpserted($tag));

            return $tag;
        });
    }

    public function deleteTag(Blog $blog, Tag $tag): void
    {
        DB::transaction(function () use ($blog, $tag) {
            $affected = $tag->articles()->pluck('articles.id');

            $tag->articles()->detach();
            $tag->delete();

            $this->events->record($blog, new TagDeleted($tag->id));
            $this->republish->handle($blog, Article::query()->whereIn('id', $affected));
            $this->audit->record('tag.deleted', null, ['name' => $tag->name], blog: $blog);
        });
    }

    /**
     * Move every article of $source to $target and delete $source.
     */
    public function mergeTags(Blog $blog, Tag $source, Tag $target): void
    {
        if ($source->is($target)) {
            throw ValidationException::withMessages(['target_id' => __('Choose a different tag.')]);
        }

        DB::transaction(function () use ($blog, $source, $target) {
            $affected = $source->articles()->pluck('articles.id');

            $target->articles()->syncWithoutDetaching($affected);
            $source->articles()->detach();
            $source->delete();

            $this->events->record($blog, new TagDeleted($source->id));
            $this->republish->handle($blog, Article::query()->whereIn('id', $affected));
            $this->audit->record('tag.merged', $target, ['from' => $source->name], blog: $blog);
        });
    }

    private function ensureNotDescendant(Category $category, string $parentId): void
    {
        for ($id = $parentId; $id !== null; $id = Category::query()->whereKey($id)->value('parent_id')) {
            if ($id === $category->id) {
                throw ValidationException::withMessages(['parent_id' => __('A category cannot be inside itself.')]);
            }
        }
    }
}
