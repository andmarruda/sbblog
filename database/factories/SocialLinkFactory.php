<?php

namespace Database\Factories;

use App\Models\AuthorProfile;
use App\Models\Blog;
use App\Models\SocialLink;
use App\Models\SocialNetwork;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialLink>
 */
class SocialLinkFactory extends Factory
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
            'author_profile_id' => null,
            'social_network_id' => SocialNetwork::factory(),
            'url' => fake()->url(),
        ];
    }

    public function forAuthor(AuthorProfile $author): static
    {
        return $this->state(fn (array $attributes) => [
            'blog_id' => $author->blog_id,
            'author_profile_id' => $author->id,
        ]);
    }
}
