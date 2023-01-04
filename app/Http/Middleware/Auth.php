<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;

class Auth
{
    /**
     * Routes name that are exceptions
     * @var         array
     */
    private array $except = [
        'admin.login',
        'admin.logout',
        'admin.checkLogin'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(!in_array($request->route()->getName(), $this->except)){
            $uc = new UserController();
            if(!$uc->isLogged())
                return $uc->redirectLoginAdmin();

            if($uc->isConfigUser())
                return $uc->redirectFirstUser();
        }

        return $next($request);
    }
}
