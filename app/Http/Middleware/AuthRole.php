<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if ($role == 'instructor') {
            if (!$request->user()->isInstructor()) {
                abort(401);
            }
        } else if ($role == 'student') {
            if (!$request->user()->isStudent()) {
                abort(401);
            }
        }
        return $next($request);
    }
}
