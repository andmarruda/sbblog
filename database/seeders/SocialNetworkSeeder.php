<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SocialNetwork;

class SocialNetworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //creating some of existing social network
        SocialNetwork::create([
            'icon' => 'images/socialnetwork/facebook.png',
            'name' => 'Facebook'
        ]);

        SocialNetwork::create([
            'icon' => 'images/socialnetwork/github.png',
            'name' => 'Github'
        ]);

        SocialNetwork::create([
            'icon' => 'images/socialnetwork/instagram.png',
            'name' => 'Instagram'
        ]);

        SocialNetwork::create([
            'icon' => 'images/socialnetwork/linkedin.png',
            'name' => 'Linkedin'
        ]);

        SocialNetwork::create([
            'icon' => 'images/socialnetwork/telegram.png',
            'name' => 'Telegram'
        ]);

        SocialNetwork::create([
            'icon' => 'images/socialnetwork/tiktok.png',
            'name' => 'TikTok'
        ]);

        SocialNetwork::create([
            'icon' => 'images/socialnetwork/twitter.png',
            'name' => 'Twitter'
        ]);

        SocialNetwork::create([
            'icon' => 'images/socialnetwork/youtube.png',
            'name' => 'Youtube'
        ]);
    }
}
