<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;

class CheckForAllScopes
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param array $scopes
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        if (!$request->user() || !$request->user()->token()) {
            throw new AuthenticationException;
        }

        foreach ($scopes as $scope) {

            if ($request->user()->tokenCan($scope)) {
                return $next($request);
            }
        }

        return response()->json(array('message' => 'Authorization failed'), 401);

    }
}
