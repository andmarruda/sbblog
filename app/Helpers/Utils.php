<?php
namespace App\Helpers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class Utils {
    /**
     * Get an article title and generates a friendly url
     * @param string $title
     * @return string
     */
    public static function getSlug(string $title) : string
    {
        $title = str_replace(' ', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title));
        return preg_replace('/[^0-9A-Za-z]/', '', $title);
    }

    /**
     * Generates a random color
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          string
     */
    public static function randomColor() : string
    {
        return '#'. substr(str_pad(rand(0x000000, 0xFFFFFF), 6, 0, STR_PAD_LEFT), 0, 6);
    }

    /**
     * Generantes a unique random color for Category or Article
     * @version         1.0.0
     * @param           Article|Category $model
     * @return          string
     */
    public static function uniqueRandomColor(Article|Category $model) : string
    {
        $class = get_class($model);
        do {
            $rand = Utils::randomColor();
        } while($class::where('bgcolor', '=', $rand)->count() > 0);

        return $rand;
    }

    /**
     * Ping google url with the sitemap
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           string $xmlName
     * @return          void
     */
    public static function sendSiteMapGoogle(string $xmlName) : void
    {
        try
        {
            $client = new \GuzzleHttp\Client();
            $client->request('GET', 'https://www.google.com/ping', ['query' => ['sitemap' => route('latestPage'). '/'. $xmlName]]);
        } catch(\Exception $err)
        {
            Log::channel('sitemap')->error($err->getFile(). ':'. $err->getLine(). ' - '. $err->getMessage());
        }
    }
}
?>