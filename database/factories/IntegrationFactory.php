<?php

namespace Database\Factories;

use App\Enums\ConsentCategory;
use App\Enums\IntegrationPlacement;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationScope;
use App\Models\Article;
use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Integration>
 */
class IntegrationFactory extends Factory
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
            'scope' => IntegrationScope::Blog,
            'author_profile_id' => null,
            'article_id' => null,
            'provider' => IntegrationProvider::Ga4,
            'config' => ['measurement_id' => 'G-'.Str::upper(Str::random(10))],
            'placement' => IntegrationPlacement::Head,
            'consent_category' => ConsentCategory::Analytics,
            'page_rule' => null,
            'enabled' => true,
            'created_by' => null,
        ];
    }

    public function forAuthor(AuthorProfile $author): static
    {
        return $this->state(fn (array $attributes) => [
            'blog_id' => $author->blog_id,
            'scope' => IntegrationScope::Author,
            'author_profile_id' => $author->id,
        ]);
    }

    public function forArticle(Article $article): static
    {
        return $this->state(fn (array $attributes) => [
            'blog_id' => $article->blog_id,
            'scope' => IntegrationScope::Article,
            'article_id' => $article->id,
        ]);
    }

    /**
     * A raw script; only allowed at blog scope.
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => IntegrationProvider::Custom,
            'config' => ['script' => '<script>console.log("sbblog")</script>'],
            'consent_category' => ConsentCategory::Functional,
        ]);
    }
}
