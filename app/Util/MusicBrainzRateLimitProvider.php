<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Util;

use Concat\Http\Middleware\RateLimitProvider;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MusicBrainzRateLimitProvider implements RateLimitProvider
{
    private $keyLastRequest = 'brainz_last_request_time';
    private $keyRequestAllowance = 'brainz_request_allowance';

    /**
     * Returns when the last request was made.
     *
     * @param RequestInterface $request
     *
     * @return float|null When the last request was made.
     */
    public function getLastRequestTime(RequestInterface $request)
    {
        return Cache::get($this->keyLastRequest);
    }

    /**
     * Used to set the current time as the last request time to be queried when
     * the next request is attempted.
     *
     * @param RequestInterface $request
     */
    public function setLastRequestTime(RequestInterface $request)
    {
        Cache::put($this->keyLastRequest, microtime(true));
    }

    /**
     * Returns what is considered the time when a given request is being made.
     *
     * @param RequestInterface $request The request being made.
     *
     * @return float Time when the given request is being made.
     */
    public function getRequestTime(RequestInterface $request)
    {
        return microtime(true);
    }

    /**
     * Returns the minimum amount of time that is required to have passed since
     * the last request was made. This value is used to determine if the current
     * request should be delayed, based on when the last request was made.
     * Returns the allowed time between the last request and the next, which
     * is used to determine if a request should be delayed and by how much.
     *
     * @param RequestInterface $request The pending request.
     *
     * @return float The minimum amount of time that is required to have passed
     *               since the last request was made (in microseconds).
     */
    public function getRequestAllowance(RequestInterface $request)
    {
        return Cache::get($this->keyRequestAllowance);
    }

    /**
     * Used to set the minimum amount of time that is required to pass between
     * this request and the next request.
     *
     * @param ResponseInterface $response The resolved response.
     */
    public function setRequestAllowance(ResponseInterface $response)
    {
        $requests = intval($response->getHeader('X-RateLimit-Remaining'));
        $seconds = 1;

        $allowance = (float) $seconds / $requests;

        Cache::put($this->keyRequestAllowance, $allowance);
    }
}
