<?php

namespace App\Http\Controllers;

use DOMDocument;
use Illuminate\Http\Request;

class SiteMapController extends Controller
{
    /**
     * Name of site map's xml
     * @var             string
     */
    private string $xmlName = 'sitemap.xml';

    /**
     * Instanceof DOMDocument
     * @var DOMDocument
     */
    private \DOMDocument $dom;

    /**
     * Generates DOMDocument when construct this class
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          void
     */
    public function __construct()
    {
        $this->dom = new DOMDocument('1.0', 'utf-8');
    }

    /**
     * Generates url tag for site map
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           string $loc
     * @param           string $lastmod
     * @param           \DOMElement $urlset
     * @return          void
     */
    private function urlTag(string $loc, string $lastmod, \DOMElement $urlset) : void
    {
        $url = $this->dom->createElement('url');

        $elementLoc = $this->dom->createElement('loc');
        $txtLoc = $this->dom->createTextNode($loc);
        $elementLoc->appendChild($txtLoc);
        $url->appendChild($elementLoc);

        $elementLst = $this->dom->createElement('lastmod');
        $txtLst = $this->dom->createTextNode($lastmod);
        $elementLst->appendChild($txtLst);
        $url->appendChild($elementLst);

        $urlset->append($url);
    }

    /**
     * Get all article urls and modified date to generates the XML
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           \DOMElement $urlset
     * @return          void
     */
    private function allArticle(\DOMElement &$urlset) : void
    {
        $all = (new ArticleController())->getAllActive();
        foreach($all as $art)
        {
            $this->urlTag(route('article', ['friendly' => $art->url_friendly, 'id' => $art->id]), $art->updated_at, $urlset);
        }
    }

    /**
     * Get all category urls and modified date to generates the XML
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          void
     */
    private function allCategory(\DOMElement &$urlset) : void
    {
        $all = (new CategoryController())->getAllActivated();
        foreach($all as $cat){
            $this->urlTag(route('latestPageCategory', ['category' => $cat->category, 'id' => $cat->id]), $cat->article()->max('updated_at'), $urlset);
        }
    }

    /**
     * Ping google url with the sitemap
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          void
     */
    private function sendSiteMapGoogle() : void
    {
        $client = new \GuzzleHttp\Client();
        $client->request('GET', 'https://www.google.com/ping', ['query' => ['sitemap' => route('latestPage'). '/'. $this->xmlName]]);
    }

    /**
     * Generates the xml of site map
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          void
     */
    public function generate()
    {
        $urlset = $this->dom->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->allArticle($urlset);
        $this->allCategory($urlset);

        $this->dom->appendChild($urlset);
        $this->dom->save(public_path(). '/'. $this->xmlName);

        $this->sendSiteMapGoogle();
    }
}
