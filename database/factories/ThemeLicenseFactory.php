<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\ThemeLicense;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ThemeLicense>
 */
class ThemeLicenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory()->premium(),
            'workspace_id' => Workspace::factory(),
            'license_key_hash' => hash('sha256', Str::random(40)),
            'valid_until' => now()->addYear(),
        ];
    }
}
