<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Middleware;

use Closure;
use Laravel\Lumen\Http\Request;

class ClientHeadersMiddleware
{
    public static $HEADER_CLIENT_ID = 'X-Datmusic-Id';
    public static $HEADER_CLIENT_VERSION = 'X-Datmusic-Version';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (config('app.client_logger.require_headers')) {
            $hasHeaders = $request->hasHeader(self::$HEADER_CLIENT_ID) && $request->hasHeader(self::$HEADER_CLIENT_VERSION);
            if (! $hasHeaders) {
                return errorResponse([
                    //so far we only have one reason to ban, so no need to check the reason
                    'id'      => 'clientHeaders',
                    'message' => 'Client headers were not provided',
                ], 400);
            }
        }

        return $next($request);
    }

    public static function getClientId(Request $request = null)
    {
        if ($request == null) {
            $request = Request::capture();
        }

        return $request->header(self::$HEADER_CLIENT_ID, 'none');
    }

    public static function getClientVersion(Request $request = null)
    {
        if ($request == null) {
            $request = Request::capture();
        }

        return $request->header(self::$HEADER_CLIENT_VERSION, 'none');
    }

    public static function getClientInfoForLogger()
    {
        return sprintf('client=id:%s,version:%s', self::getClientId(), self::getClientVersion());
    }
}
