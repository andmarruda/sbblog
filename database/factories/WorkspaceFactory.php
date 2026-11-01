<?php

namespace Database\Factories;

use App\Enums\TwoFactorPolicy;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->company(),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'owner_user_id' => User::factory(),
            'two_factor_policy' => TwoFactorPolicy::Off,
            'plan' => 'community',
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Workspace $workspace) {
            $workspace->members()->syncWithoutDetaching([
                $workspace->owner_user_id => ['workspace_role' => WorkspaceRole::Owner],
            ]);
        });
    }
}
