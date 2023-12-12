<?php

namespace App\Http\View\Composers;

use App\Models\Category;
use Illuminate\View\View;
use App\Helpers\Utils;

class ArticleListComposer 
{
    public function compose(View $view)
    {
        $confirm = Utils::translateModalConfirm();
        $view->with('confirm_destroy', $confirm['confirm_destroy']);
    }
}