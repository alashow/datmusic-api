<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;

class HttpClient
{
    /**
     * @var Client Guzzle client
     */
    private $httpClient;

    /**
     * HttpClientTrait constructor.
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
            return $request->withUri(Uri::withQueryValue($request->getUri(), 'v', '5.71'));
        }));

        $this->httpClient = new Client([
                'base_uri' => 'https://api.vk.com',
                'headers'  => [
                    'User-Agent' => 'KateMobileAndroid/48.2 lite-433 (Android 8.1.0; SDK 27; arm64-v8a; Google Pixel 2 XL; en)',
                ],
                'handler'  => $handler,
            ] + $config);
    }

    public function getClient()
    {
        return $this->httpClient;
    }
}
