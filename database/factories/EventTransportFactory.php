<?php

namespace Database\Factories;

use App\Enums\TransportDriver;
use App\Models\Blog;
use App\Models\EventTransport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventTransport>
 */
class EventTransportFactory extends Factory
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
            'driver' => TransportDriver::Webhook,
            'config' => ['url' => fake()->url(), 'secret' => Str::random(32)],
            'enabled' => true,
            'last_dispatched_sequence' => 0,
            'last_error_at' => null,
            'last_error' => null,
        ];
    }
}
