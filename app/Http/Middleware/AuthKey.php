<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthKey
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
        $token = $request->header('apikey');
        if($token != 'ABCDEFGH')
        {
            return response()->json([
                'success' => false,
                'message' => 'App key not found'
            ],401);
        }
        return $next($request);
    }
}
