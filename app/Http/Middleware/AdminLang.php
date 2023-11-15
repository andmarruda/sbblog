<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Models\Language;

class AdminLang
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $lang_id = Language::defaultLocale();
        if(auth()->check())
        {
            $lang = Language::find(auth()->user()->language_id);
            $lang_id = ($lang) ? $lang->lang_id : $lang_id;
        }
        
        App::setLocale($lang_id);
        return $next($request);
    }
}
