<?php

namespace Database\Factories;

use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuthorProfile>
 */
class AuthorProfileFactory extends Factory
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
            'user_id' => User::factory(),
            'display_name' => $name = fake()->name(),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'bio' => fake()->paragraph(),
            'avatar_media_id' => null,
        ];
    }

    /**
     * A guest author without a user account.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }
}
