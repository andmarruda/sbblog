<?php

namespace App\Http\View\Composers;

use App\Models\Category;
use Illuminate\View\View;

class ArticleFundamentalsComposer 
{
    public function compose(View $view)
    {
        $view->with('categories', Category::orderBy('category')->get());
    }
}