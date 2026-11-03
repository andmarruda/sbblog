<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
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
            'email' => fake()->unique()->safeEmail(),
            'workspace_role' => WorkspaceRole::Member,
            'blog_id' => null,
            'role_id' => null,
            'token_hash' => Invitation::hashToken(Str::random(40)),
            'invited_by' => null,
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    /**
     * Use a known plain token, for tests that follow the link.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token_hash' => Invitation::hashToken($token),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
