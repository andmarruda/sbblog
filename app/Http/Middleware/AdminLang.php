<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\UserController;
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
        $uc = new UserController();
        $locale = $uc->getLocale() ?? Language::defaultLocale();
        App::setLocale($locale);

        return $next($request);
    }
}
