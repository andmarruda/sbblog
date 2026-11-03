<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Never a real locale, so it can't clash with seeded languages (pt-BR, en-US…).
            'code' => 'x-'.Str::lower(Str::random(10)),
            'label' => fake()->country(),
        ];
    }
}
