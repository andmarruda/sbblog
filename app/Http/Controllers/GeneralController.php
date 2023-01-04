<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SocialNetworkUrl;
use App\Models\SocialNetwork;
use App\Models\General;

class GeneralController extends Controller
{
    /**
     * Data validations
     * var      array
     */
    private array $validations = [
        'slogan'            => 'required|min:45|max:200|string',
        'section'           => 'required|min:5|max:100|string',
        'page_title'        => 'required|min:5|max:110|string',
        'page_description'  => 'required|min:45|max:200|string',
        'autoconvert_webp'  => 'required'
    ];

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
     * Display the specified resource.
     *
     * @param  General $general
     * @return \Illuminate\Http\Response
     */
    public function show(General $general)
    {
        return $general;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  General $general
     * @return \Illuminate\Http\Response
     */
    public function edit(General $general)
    {
        return view('general', ['gen' => $general]);
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
        $filepath = $general->brand_image;
        if($request->hasFile('brand_image') && $request->file('brand_image')->isValid()){
            $request->validate(ImageController::validateFile($this->validations, 'brand_image'));
            $extension = $request->file('brand_image')->extension();
            $stored = $request->file('brand_image')->store('public');
            $filepath = ($request->input('autoconvert_webp')) ? ImageController::imageToWebp($stored, $extension, true) : $stored;
        } else
            $request->validate($this->validations);

        $general->fill([
            'brand_image' => $filepath,
            'slogan' => $request->input('slogan'),
            'section' => $request->input('section'),
            'active' => true,
            'google_analytics' => $request->input('google_analytics'),
            'google_ads_script' => $request->input('google_ads_script'),
            'google_optimize_script' => $request->input('google_optimize_script'),
            'title' => $request->input('page_title'),
            'description' => $request->input('page_description'),
            'autoconvert_webp' => $request->input('autoconvert_webp')
        ]);
        $saved = $general->save();

        if(!is_null($request->input('socialnetwork')))
            $this->saveSocialNetwork($request->input('socialnetwork'), $general->id);

        return redirect()->route('general.edit')->with('saved', $saved);
    }
}
