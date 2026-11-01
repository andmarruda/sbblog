<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blog_id' => Blog::factory(),
            'author_profile_id' => fn (array $attributes) => AuthorProfile::factory()->state(['blog_id' => $attributes['blog_id']]),
            'category_id' => fn (array $attributes) => Category::factory()->state(['blog_id' => $attributes['blog_id']]),
            'language_id' => fn (array $attributes) => Blog::query()->whereKey($attributes['blog_id'])->value('default_language_id'),
            'translation_group_id' => (string) Str::uuid7(),
            'slug' => Str::slug(fake()->sentence(5)).'-'.Str::lower(Str::random(4)),
            'status' => ArticleStatus::Draft,
            'bgcolor' => null,
            'scheduled_at' => null,
            'published_at' => null,
            'created_by' => null,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        // Every article starts with revision #1; published articles also go live with it.
        return $this->afterCreating(function (Article $article) {
            if ($article->current_revision_id !== null) {
                return;
            }

            $revision = ArticleRevision::factory()->for($article)->create();

            $article->current_revision_id = $revision->id;

            if ($article->status === ArticleStatus::Published) {
                $article->published_revision_id = $revision->id;
            }

            $article->save();
        });
    }

    public function inReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::InReview,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Scheduled,
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Published,
            'published_at' => fake()->dateTimeBetween('-1 year'),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Archived,
        ]);
    }
}
