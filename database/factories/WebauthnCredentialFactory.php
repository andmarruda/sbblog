<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebauthnCredential>
 */
class WebauthnCredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['YubiKey', 'iPhone', 'MacBook']),
            'credential_id' => base64_encode(Str::random(64)),
            'public_key' => base64_encode(Str::random(128)),
            'sign_count' => 0,
            'last_used_at' => null,
        ];
    }
}
