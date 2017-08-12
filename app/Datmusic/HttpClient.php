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
            'base_uri' => 'https://m.vk.com',
            'cookies'  => true,
            'headers'  => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36',
            ],
        ] + $config);
    }

    public function getClient()
    {
        return $this->httpClient;
    }
}
