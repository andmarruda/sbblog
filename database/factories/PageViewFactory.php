<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\PageView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageView>
 */
class PageViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'blog_id' => fn (array $attributes) => Article::query()->whereKey($attributes['article_id'])->value('blog_id'),
            'author_profile_id' => fn (array $attributes) => Article::query()->whereKey($attributes['article_id'])->value('author_profile_id'),
            'frontend_id' => null,
            'visitor_hash' => hash('sha256', fake()->uuid()),
            'referrer_host' => fake()->optional()->domainName(),
            'country' => fake()->countryCode(),
            'device' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'duration_ms' => fake()->numberBetween(1_000, 600_000),
            'viewed_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }
}
