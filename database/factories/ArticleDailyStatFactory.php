<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleDailyStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleDailyStat>
 */
class ArticleDailyStatFactory extends Factory
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
            'day' => fake()->unique()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'blog_id' => fn (array $attributes) => Article::query()->whereKey($attributes['article_id'])->value('blog_id'),
            'author_profile_id' => fn (array $attributes) => Article::query()->whereKey($attributes['article_id'])->value('author_profile_id'),
            'views' => $views = fake()->numberBetween(0, 5_000),
            'unique_visitors' => (int) ($views * fake()->randomFloat(2, 0.4, 0.9)),
            'avg_duration_ms' => fake()->numberBetween(5_000, 300_000),
        ];
    }
}
