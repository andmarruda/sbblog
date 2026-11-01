<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\OutboxEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OutboxEvent>
 */
class OutboxEventFactory extends Factory
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
            // Takes the next number from the blog's counter, as the real writer does.
            'sequence' => function (array $attributes) {
                $blog = Blog::query()->whereKey($attributes['blog_id'])->firstOrFail();
                $blog->increment('event_sequence');

                return $blog->event_sequence;
            },
            'type' => 'article.published',
            'aggregate_type' => 'article',
            'aggregate_id' => (string) Str::uuid7(),
            'schema_version' => 1,
            'payload' => [],
            'occurred_at' => now(),
        ];
    }
}
