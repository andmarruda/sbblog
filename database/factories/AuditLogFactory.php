<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
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
            'blog_id' => null,
            'actor_user_id' => User::factory(),
            'actor_app_installation_id' => null,
            'action' => 'workspace.updated',
            'subject_type' => null,
            'subject_id' => null,
            'metadata' => [],
            'ip_hash' => hash('sha256', fake()->ipv4()),
        ];
    }
}
