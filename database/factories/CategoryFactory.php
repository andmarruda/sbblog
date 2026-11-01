<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
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
            'parent_id' => null,
            'name' => $name = Str::title(fake()->word().' '.fake()->word()),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'bgcolor' => fake()->hexColor(),
            'position' => fake()->numberBetween(0, 20),
        ];
    }
}
