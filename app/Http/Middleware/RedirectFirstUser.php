<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectFirstUser
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
        if(auth()->check() && auth()->user()->firstUserLogged()->count() > 0 && !in_array($request->route()->getName(), ['user.create', 'user.store']))
            return redirect()->route('user.create');

        return $next($request);
    }
}
