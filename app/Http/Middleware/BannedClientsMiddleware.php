<?php
/**
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Middleware;

use App\Datmusic\CachesTrait;
use Closure;
use Laravel\Lumen\Http\Request;

class BannedClientsMiddleware
{
    use CachesTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->isClientBanned($request)) {
            logger()->bannedClientRequest();

            return errorResponse([
                //so far we only have one reason to ban, so no need to check the reason
                'id'      => 'rateLimit.tooManyCaptchas',
                'message' => 'Too many captcha attempts',
            ], 429, ['X-Quota-Policy' => sprintf('%s;window=%s', config('app.captcha_lock.allowed_failed_attempts'), config('app.captcha_lock.allowed_failed_attempts_duration'))]);
        }

        return $next($request);
    }
}
