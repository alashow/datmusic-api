<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Middleware;

use Closure;

class HttpBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (env('BASIC_AUTH_ENABLED', true)) {
            $envs = [
                'staging',
                'production',
            ];

            if (in_array(app()->environment(), $envs)) {
                if ($request->getUser() != env('API_USERNAME') || $request->getPassword() != env('API_PASSWORD')) {
                    $headers = ['WWW-Authenticate' => 'Basic'];

                    return response('Unauthorized', 401, $headers);
                }
            }
        }

        return $next($request);
    }
}
