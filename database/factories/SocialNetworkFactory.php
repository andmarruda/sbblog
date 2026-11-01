<?php

namespace Database\Factories;

use App\Models\SocialNetwork;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialNetwork>
 */
class SocialNetworkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => Str::slug($name = fake()->unique()->company()),
            'name' => $name,
            'icon' => null,
        ];
    }
}
