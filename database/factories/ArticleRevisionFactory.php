<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleRevision>
 */
class ArticleRevisionFactory extends Factory
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
            'number' => fn (array $attributes) => (int) ArticleRevision::query()
                ->where('article_id', $attributes['article_id'])
                ->max('number') + 1,
            'title' => fake()->sentence(6),
            'description' => fake()->sentence(18),
            'body' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => fake()->sentence(4)]]],
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => fake()->paragraph(5)]]],
                    ['type' => 'paragraph', 'content' => [
                        ['type' => 'text', 'text' => fake()->sentence(8).' '],
                        ['type' => 'text', 'text' => fake()->words(2, true), 'marks' => [['type' => 'bold']]],
                    ]],
                ],
            ],
            'cover_media_id' => null,
            'created_by_user_id' => null,
            'created_by_app_installation_id' => null,
        ];
    }
}
