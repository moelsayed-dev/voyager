<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiGuestUser
{
    public function handle(Request $request, Closure $next)
    {
        if(auth('sanctum')->check()) {
            return $next($request);
        } else {
            return response()->json(['status' => 'Unauthenticated', 'message' => 'You must be logged in'], 401);
        }
    }
}
