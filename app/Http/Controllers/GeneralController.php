<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SocialNetworkUrl;
use App\Models\General;
use App\Models\CommentConfig;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Image;

class GeneralController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('general');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate($this->validations);
        //preparing the idea for multiplo language public page
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  General $general
     * @return \Illuminate\Http\Response
     */
    public function edit(General $general)
    {
        return view('general', ['gen' => $general, 'comment_configs' => CommentConfig::where('language_id', '=', auth()->user()->language_id)->get()]);
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  General $general
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, General $general) : \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'slogan'            => 'required|min:45|max:200|string',
            'section'           => 'required|min:5|max:100|string',
            'page_title'        => 'required|min:5|max:110|string',
            'page_description'  => 'required|min:45|max:200|string',
            'autoconvert_webp'  => 'required',
            'comment_config_id' => 'required',
            'brand_image'       => 'sometimes|mimes:'. config('upload.mimes'). '|max:'. config('upload.maxsize')
        ]);
        
        $filepath = $general->brand_image;
        if($request->hasFile('brand_image') && $request->file('brand_image')->isValid())
            $filepath = Image::generalBrandImage($request, $request->input('autoconvert_webp'));

        $saved = $general->update([...$request->all(), 
            'brand_image' => $filepath,
            'active' => true,
            'title' => $request->input('page_title'),
            'description' => $request->input('page_description')
        ]);

        if(!is_null($request->input('socialnetwork')))
            $this->saveSocialNetwork($request->input('socialnetwork'), $general->id);

        return redirect()->route('general.edit', $general->id)->with('saved', $saved);
    }
}
