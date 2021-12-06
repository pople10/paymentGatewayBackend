<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if(!$request->user()->verified)
            return response()->json([ 'error' => "Your account is not verified yet" ], 422);
        if($request->user()->deleted)
            return response()->json([ 'error' => "Your account is deleted" ], 422);
        if(!$request->user()->enabled)
            return response()->json([ 'error' => "Your account is disabled" ], 422);
        return $next($request);
    }
}
