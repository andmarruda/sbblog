<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;

class AdminController extends Controller
{
    /**
     * Generate login interface
     *
     * @return view
     */
    public function loginInterface()
    {
        return view('admin');
    }

    /**
     * Generate dashboard interface calling the view
     * 
     * @return view
     */
    public function dashboardInterface()
    {
        $cat = new \App\Models\Category();
        $catStat = $cat->statistics()->toJson();

        $art = new Article();
        $artStat = $art->statistics();

        return view('dashboard', [
            'articles' => Article::latests()->paginate(config('sbblog.page_limit')), 
            'catStat' => $catStat, 
            'artStat' => $artStat]
        );
    }
}
