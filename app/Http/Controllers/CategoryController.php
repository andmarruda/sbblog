<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Data validations
     * var      array
     */
    private array $validations = [
        'category' => 'required|min:5|max:50|string'
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $all = Category::withTrashed()->get()->sortBy('category');
        return view('category-list', ['category' => $all]);
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
        $request->validate($this->validations);
        $saved = Category::create([...$request->all(), 'user_id' => auth()->user()->id]);
        $this->sitemap();

        return redirect()->route('category.create')
                ->with('saved', $saved)
                ->with('saved-message', __('adminTemplate.category.okmessage'));
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
        $request->validate($this->validations);
        $saved = $category->update([...$request->all(), 'user_id' => auth()->user()->id]);
        $this->sitemap();

        return redirect()->route('category.edit', $category->id)
            ->with('saved', $saved)
            ->with('saved-message', __('adminTemplate.category.okmessage'));
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param   int $id
     * @return  \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $category = Category::withTrashed()->find($id);
        is_null($category->deleted_at) ? $category->delete() : $category->restore();
        return redirect()->route('category.index');
    }
}