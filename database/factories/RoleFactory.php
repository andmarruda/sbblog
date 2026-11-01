<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'key' => Str::slug($name = fake()->unique()->jobTitle()),
            'name' => $name,
            'is_system' => false,
            'requires_two_factor' => false,
        ];
    }

    /**
     * A built-in role, shared by every workspace.
     */
    public function builtIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => null,
            'is_system' => true,
        ]);
    }
}
