<?php

namespace Database\Factories;

use App\Enums\StoreAdapter;
use App\Models\Blog;
use App\Models\Frontend;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Frontend>
 */
class FrontendFactory extends Factory
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
            'name' => fake()->domainWord(),
            'base_url' => fake()->url(),
            'store_adapter' => StoreAdapter::Sqlite,
            'sync_token_hash' => hash('sha256', Str::random(40)),
            'last_acked_sequence' => 0,
            'last_sync_at' => null,
            'core_version' => '0.1.0',
        ];
    }
}
