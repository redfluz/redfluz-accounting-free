<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * @noinspection PhpUnused
 */
class SetUserLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  Closure(Request): (Response)  $next  The next middleware handler.
     * @return Response The HTTP response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (is_string($locale)) {
            App::setLocale($locale);
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
