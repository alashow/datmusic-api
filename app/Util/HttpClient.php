<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Util;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;

class HttpClient
{
    /**
     * @var Client Guzzle client
     */
    private $httpClient;

    /**
     * HttpClient constructor.
     */
    public function __construct()
    {
        $config = [];

        if (env('PROXY_ENABLE', false)) {
            $proxy = sprintf('%s://', env('PROXY_METHOD'));

            if (! empty(env('PROXY_USERNAME')) && ! empty(env('PROXY_PASSWORD'))) {
                $proxy .= sprintf('%s:%s@', env('PROXY_USERNAME'), env('PROXY_PASSWORD'));
            }

            $proxy .= sprintf('%s:%s', env('PROXY_IP'), env('PROXY_PORT'));
            $config = ['proxy' => $proxy];
        }
        $handler = HandlerStack::create();
        $handler->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request->withUri(Uri::withQueryValue($request->getUri(), 'v', '5.119'));
        }));

        $this->httpClient = new Client([
            'base_uri' => 'https://api.vk.com',
            'headers'  => [
                'User-Agent' => 'VKAndroidApp/5.52-4543 (Android 5.1.1; SDK 22; x86_64; unknown Android SDK built for x86_64; en; 320x240)',
            ],
            'handler'  => $handler,
            ] + $config);
    }

    public function getClient()
    {
        return $this->httpClient;
    }
}
