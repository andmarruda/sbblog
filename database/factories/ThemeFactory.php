<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => $slug = Str::slug(fake()->unique()->word().' '.fake()->word()),
            'name' => Str::title(Str::replace('-', ' ', $slug)),
            'package_name' => '@sbblog/theme-'.$slug,
            'latest_version' => '1.0.0',
            'is_premium' => false,
            'price_cents' => 0,
            'currency' => 'USD',
        ];
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_premium' => true,
            'price_cents' => fake()->randomElement([2900, 4900, 7900]),
        ]);
    }
}
