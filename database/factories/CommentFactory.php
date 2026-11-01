<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
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
            'parent_id' => null,
            'author_name' => fake()->name(),
            'author_email_hash' => hash('sha256', fake()->safeEmail()),
            'body' => fake()->paragraph(),
            'status' => CommentStatus::Pending,
            'is_public' => false,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'moderated_by' => null,
            'moderated_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommentStatus::Approved,
            'is_public' => true,
            'moderated_at' => now(),
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommentStatus::Spam,
            'moderated_at' => now(),
        ]);
    }
}
