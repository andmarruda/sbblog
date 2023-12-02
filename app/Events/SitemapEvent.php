<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SitemapEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Handle with the event
     *
     * @return void
     */
    public function handle() : void
    {
        
    }
}
