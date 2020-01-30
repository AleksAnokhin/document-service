<?php

namespace App\Http\Middleware;

use Closure;

class adminGate
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
        $user = $request->user();
        if($user->hasAnyRole(['operator','admin'])) return $next($request);
        return false;
    }
}
