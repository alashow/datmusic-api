<?php
/**
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Middleware;

use Closure;
use Laravel\Lumen\Http\Request;

class ResponseTimeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        return $response->header('X-Response-Time', self::secondsSinceRequest());
    }

    public static function secondsSinceRequest()
    {
        return round(microtime(true) - LUMEN_START, 3);
    }
}
