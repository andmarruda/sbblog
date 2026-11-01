<?php

namespace Database\Factories;

use App\Models\ArticleSuggestion;
use App\Models\SuggestionComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuggestionComment>
 */
class SuggestionCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'suggestion_id' => ArticleSuggestion::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(10),
            'anchor' => null,
        ];
    }
}
