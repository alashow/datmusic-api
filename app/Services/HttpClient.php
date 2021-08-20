<?php
/*
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

/**
 * Basic class to configure common http client
 * Class HttpClient.
 */
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
        $handler = HandlerStack::create();
        $this->httpClient = new Client([
            'handler'  => $handler,
        ]);
    }

    public function getClient()
    {
        return $this->httpClient;
    }
}
