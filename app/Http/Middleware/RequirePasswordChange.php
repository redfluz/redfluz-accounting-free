<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * @param  Request  $request  The HTTP request instance.
     * @param  Closure(Request): Response  $next  The next middleware handler.
     * @return Response The HTTP response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password
            && ! $request->routeIs('personnel.profile.edit', 'personnel.profile.update', 'logout')) {
            return redirect()->route('personnel.profile.edit');
        }

        return $next($request);
    }
}
