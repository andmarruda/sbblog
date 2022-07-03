<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\General;
use App\Models\SocialNetwork;
use App\Models\SocialNetworkUrl;

class GeneralController extends Controller
{
    /**
     * Allowed file extension
     * @var             array
     */
    CONST ALLOWED_EXTENSION = ['jpg', 'jpeg', 'bmp', 'gif', 'png', 'webp'];

    /**
     * Allowd max file size
     * @var             int
     */
    CONST ALLOWED_SIZE = 1500000;

    /**
     * Gererates the interface of General configuration form
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           ?string $message
     * @return          view
     */
    public function generalInterface(?string $message=NULL, ?bool $saved=NULL)
    {
        $attrs = ['gen' => General::find(1)];
        if(!is_null($message))
            $attrs['message'] = $message;

        if(!is_null($saved))
            $attrs['saved'] = $saved;

        return view('general', $attrs);
    }

    /**
     * Saves all the URL passed for social networks
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           array $socialNetwork
     * @param           int $general_id
     * @return          void
     */
    private function saveSocialNetwork(array $socialNetwork, int $general_id) : void
    {
        foreach($socialNetwork as $id => $url){
            if(is_null($url))
                continue;

            $sn = SocialNetworkUrl::where('social_network_id', '=', $id)->where('general_id', '=', $general_id);
            $sn = ($sn->count() == 0) ? new SocialNetworkUrl() : $sn->first();

            $sn->fill([
                'general_id' => $general_id,
                'social_network_id' => $id,
                'url' => $url
            ]);
            $sn->save();
        }
    }

    /**
     * Saves the general configuration
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          view
     */
    public function generalSave(Request $req)
    {
        $filepath = $req->input('registered_file');
        if($req->hasFile('brand_image')){
            if(!$req->file('brand_image')->isValid() || !in_array($req->file('brand_image')->extension(), self::ALLOWED_EXTENSION) || $req->file('brand_image')->getSize() > self::ALLOWED_SIZE)
                return $this->generalInterface(__('adminTemplate.general.imageError'), false);

            $filepath = basename($req->file('brand_image')->store('public'));
        }

        $gen = General::find(1);
        $gen->fill([
            'brand_image' => $filepath,
            'slogan' => $req->input('slogan'),
            'section' => $req->input('section'),
            'active' => true,
            'google_analytics' => $req->input('google_analytics'),
            'google_ads_script' => $req->input('google_ads_script'),
            'google_optimize_script' => $req->input('google_optimize_script')
        ]);
        $saved = $gen->save();

        if(!is_null($req->input('socialnetwork')))
            $this->saveSocialNetwork($req->input('socialnetwork'), $gen->id);

        return $this->generalInterface(saved: $saved);
    }
}
