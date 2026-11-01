<?php

namespace Database\Factories;

use App\Enums\SuggestionSource;
use App\Enums\SuggestionStatus;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ArticleSuggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleSuggestion>
 */
class ArticleSuggestionFactory extends Factory
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
            'base_revision_id' => fn (array $attributes) => Article::query()->whereKey($attributes['article_id'])->value('current_revision_id'),
            'proposed_revision_id' => fn (array $attributes) => ArticleRevision::factory()->state(['article_id' => $attributes['article_id']]),
            'status' => SuggestionStatus::Pending,
            'source' => SuggestionSource::Human,
            'requested_by_user_id' => User::factory(),
            'requested_by_app_installation_id' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function fromAi(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => SuggestionSource::Ai,
            'requested_by_user_id' => null,
        ]);
    }
}
