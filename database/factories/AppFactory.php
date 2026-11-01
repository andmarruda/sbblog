<?php

namespace Database\Factories;

use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<App>
 */
class AppFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => null,
            'name' => $name = fake()->unique()->company(),
            'slug' => Str::slug($name),
            'is_first_party' => false,
            'client_id' => Str::random(40),
            'client_secret_hash' => Hash::make(Str::random(40)),
            'redirect_uris' => [fake()->url()],
            'requested_scopes' => ['articles:read'],
        ];
    }

    public function firstParty(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_first_party' => true,
        ]);
    }
}
