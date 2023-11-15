<?php

namespace App\Http\View\Composers;
use Illuminate\View\View;
use App\Models\Language;

class AdminTemplateComposer 
{
    public function compose(View $view)
    {
        $view->with('lang', Language::getSelectedLang())
            ->with('languages', Language::orderBy('label')->get());
    }
}