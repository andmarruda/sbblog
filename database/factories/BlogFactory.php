<?php

namespace Database\Factories;

use App\Enums\CommentPolicy;
use App\Models\Blog;
use App\Models\Language;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Blog>
 */
class BlogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => $name = Str::title(fake()->word().' '.fake()->word()),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'canonical_url' => fake()->url(),
            'title' => $name,
            'slogan' => fake()->sentence(4),
            'description' => fake()->sentence(12),
            'default_language_id' => Language::factory(),
            'comment_policy' => CommentPolicy::Moderated,
            'autoconvert_webp' => true,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Blog $blog) {
            $blog->languages()->syncWithoutDetaching([$blog->default_language_id]);
        });
    }
}
