<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\Category;
use \App\Models\Util;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class CategoryController extends Controller
{
    /**
     * Gererates the interface of Category form and search
     * 
     * @return view
     */
    public function categoryInterface(?int $id=NULL)
    {
        if(!is_null($id)){
            $c = Category::find($id);
            $attrs = $c->count() > 0 ? ['cat' => $c] : [];
        }
        return view('category', $attrs ?? []);
    }

    /**
     * Generates a random color to the category
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
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

    /**
     * List all category ordered by name
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public function getAll()
    {
        return Category::all()->sortBy('category');
    }

    /**
     * List all activated category ordered by name
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           
     * @return          \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public function getAllActivated()
    {
        return Category::where('active', '=', true)->orderBy('category')->get();
    }

    /**
     * When trys to save the value from form
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          view
     */
    public function categoryFormPost(Request $req)
    {
        $c = is_null($req->input('id')) ? new Category() : Category::find($req->input('id'));
        $c->fill([
            'color' => is_null($req->input('id')) ? $this->generatesRandomColor() : $c->color,
            'active' => $req->input('active'),
            'category' => $req->input('categoryName'),
            'user_id' => 1
        ]);

        $saved = $c->save();
        return view('category', ['saved' => $saved]);
    }

    /**
     * Search categories
     * @version         1.0.0
     * @author          Anderson Arruda < andmarruda@gmail.com >
     * @param           Request $req
     * @return          NEVER JSON
     */
    public function categorySearch(Request $req)
    {
        $c = Category::where('category', 'ILIKE', '%'. $req->input('categorySearch'). '%')->get();
        header('Content-Type: application/json; charset=utf-8');
        echo $c->toJson();
    }
}