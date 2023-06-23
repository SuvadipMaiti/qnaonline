<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class CheckAdmintype
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(@Auth::user() && Auth::user()->type != 'admin')
        {
            return redirect('/admin/login');
        }
        return $next($request);
    }
}
