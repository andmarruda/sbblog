<?php

namespace Database\Seeders;

use App\Models\SocialNetwork;
use Illuminate\Database\Seeder;

class SocialNetworkSeeder extends Seeder
{
    /**
     * Seed the supported social networks.
     */
    public function run(): void
    {
        $networks = [
            'x' => 'X',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'linkedin' => 'LinkedIn',
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok',
            'github' => 'GitHub',
            'bluesky' => 'Bluesky',
            'threads' => 'Threads',
            'mastodon' => 'Mastodon',
        ];

        foreach ($networks as $key => $name) {
            SocialNetwork::query()->updateOrCreate(['key' => $key], ['name' => $name, 'icon' => $key]);
        }
    }
}
