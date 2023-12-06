<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Helpers\Utils;
use App\Models\Article;
use App\Models\Category;

class SitemapEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    const FILENAME = 'sitemap.xml';
    const URL_TAG = '<url><loc>%s</loc><lastmod>%s</lastmod></url>';

    /**
     * Handle with the event
     *
     * @return void
     */
    public function handle() : void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">%s</urlset>';

        $urls = '';

        Article::orderBy('updated_at', 'DESC')->get()->map(function ($model) use(&$urls) {
            $urls .= sprintf(self::URL_TAG, route('article', ['friendly' => $model->url_friendly, 'id' => $model->id]), $model->updated_at->format('Y-m-d'));
        });

        Category::orderBy('updated_at', 'DESC')->get()->map(function ($model) use(&$urls) {
            $urls .= sprintf(self::URL_TAG, route('latestPageCategory', ['category' => $model->category, 'id' => $model->id]), $model->updated_at->format('Y-m-d'));
        });
        
        $xml = sprintf($xml, $urls);

        file_put_contents(public_path(self::FILENAME), $xml);

        Utils::sendSiteMapGoogle(self::FILENAME);
    }
}
