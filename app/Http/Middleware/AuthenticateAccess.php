<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class AuthenticateAccess
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
        $validsecrets = explode(',', env('ACCEPTED_KEYS'));
        if (in_array($request->bearerToken(), $validsecrets)) {
            return $next($request);
        }

        abort(Response::HTTP_UNAUTHORIZED);

    }
}
