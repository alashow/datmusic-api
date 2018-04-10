<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use GuzzleHttp\Client;

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

        $this->httpClient = new Client([
                'base_uri' => 'http://api.xn--41a.ws',
                'headers'  => [
                    'User-Agent' => 'datmusic-api',
                ],
            ] + $config);
    }

    public function getClient()
    {
        return $this->httpClient;
    }
}
