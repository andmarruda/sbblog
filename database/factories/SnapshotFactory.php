<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\Snapshot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Snapshot>
 */
class SnapshotFactory extends Factory
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
            'up_to_sequence' => 0,
            'storage_path' => 'snapshots/'.Str::uuid7().'.sqlite',
            'size_bytes' => fake()->numberBetween(10_000, 50_000_000),
        ];
    }
}
