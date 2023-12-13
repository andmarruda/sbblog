<?php

namespace App\Http\View\Composers;

use App\Models\Category;
use Illuminate\View\View;
use App\Models\General;

class PublicTemplateComposer 
{
    public function compose(View $view)
    {
        $view->with('category', Category::orderBy('category')->get())
            ->with('gen', General::find(1));
    }
}