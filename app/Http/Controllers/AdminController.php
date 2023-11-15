<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $c = new ArticleController();
        $lasts = $c->getLasts();

        $cat = new \App\Models\Category();
        $catStat = $cat->statistics()->toJson();

        $art = new \App\Models\Article();
        $artStat = $art->statistics();

        return view('dashboard', ['articles' => $lasts, 'catStat' => $catStat, 'artStat' => $artStat]);
    }
}
