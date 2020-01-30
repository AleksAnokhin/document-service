<?php

namespace App\Http\Middleware;

use App\BusinessUploads;
use Closure;

class protectBusinessVerifiedDocs
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
        $arr = explode('/',$request->input('q'));
        if(!isset($arr[1])) return response()->json(['message'=>'Error!'],400);
        $data = array_slice($arr, -2, 2);
        $user_id = $data[0];
        $filename = $data[1];
        $document = BusinessUploads::where(['user_id' => $user_id, 'name' => $filename])->first();
        if(!empty($document)) {
            if($document->status_id !== 7) return $next($request);
            $admin = $user->hasRole('admin') ? true : false;
            if($admin) return $next($request);
            return response()->json(['message'=>'Not allowed'],400);
        }

        return $next($request);
    }
}
