<?php

namespace Database\Factories;

use App\Enums\MediaDisk;
use App\Models\Blog;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
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
            'uploaded_by' => null,
            'disk' => MediaDisk::Local,
            'path' => 'media/'.Str::uuid7().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(20_000, 2_000_000),
            'width' => 1600,
            'height' => 900,
            'alt_text' => fake()->sentence(4),
            'variants' => null,
            'checksum' => hash('sha256', Str::random(32)),
        ];
    }
}
