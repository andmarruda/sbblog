<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\BlogTheme;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlogTheme>
 */
class BlogThemeFactory extends Factory
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
            'theme_id' => Theme::factory(),
            'version' => fn (array $attributes) => Theme::query()->whereKey($attributes['theme_id'])->value('latest_version'),
            'config' => [],
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
