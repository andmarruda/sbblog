<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  General $general
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, General $general)
    {
        $filepath = $general->brand_image;
        if($request->hasFile('brand_image') && $request->file('brand_image')->isValid()){
            $request->validate(ImageController::validateFile($this->validations, 'brand_image'));
        } else
            $request->validate($this->validations);
    }
}
