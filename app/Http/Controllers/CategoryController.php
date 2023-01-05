<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use \App\Models\Util;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Sets informations from form to model
     * @author  Anderson Arruda < contato@sysborg.com.br >
     * @param   Category $category
     * @param   Request $request
     * @return  void
     */
    private function fill(Category &$category, Request $request) : void
    {
        $category->fill([
            'color' => is_null($request->input('id')) ? $this->generatesRandomColor() : $category->color,
            'active' => $request->input('active'),
            'category' => $request->input('categoryName'),
            'user_id' => 1
        ]);
    }

    /**
     * Generate new site map
     * @author  Anderson Arruda < contato@sysborg.com.br >
     * @param   
     * @return  void
     */
    private function sitemap() : void
    {
        $sm = new SiteMapController();
        $sm->generate();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('category');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request) : \Illuminate\Http\RedirectResponse
    {
        $category = new Category();
        $this->fill($category, $request);
        $saved = $category->save();
        $this->sitemap();

        return redirect()->route('category.create')->with('saved', $saved);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Category $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        return view('category', ['cat' => $category]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Category $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $this->fill($category, $request);
        $saved = $category->save();
        $this->sitemap();

        return redirect()->route('category.edit', $category->id)->with('saved', $saved);
    }

    /**
     * Generates a random color to the category
     * @version         1.0.0
     * @author          Anderson Arruda < contato@sysborg.com.br >
     * @param           
     * @return          string
     */
    public function generatesRandomColor() : string
    {
        $rand = Util::randomColor();
        $search = Category::where('color', $rand);
        while($search->count() > 0){
            $rand = Util::randomColor();
            $search = Category::where('color', $rand);
        }

        return $rand;
    }
}
