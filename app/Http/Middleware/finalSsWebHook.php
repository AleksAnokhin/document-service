<?php

namespace App\Http\Middleware;

use Closure;

class finalSsWebHook
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
        $request->validate([
            'applicantId' => 'required|string',
            'inspectionId'     => 'required|string',
            'correlationId'     => 'required|string',
            //'externalUserId'     => 'required|string',
            'type'     => 'required|string',
            'reviewStatus'     => 'required|string',
            //'reviewResult'     => 'required',
        ]);
        return $next($request);
    }
}
