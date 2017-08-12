<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Providers;

use App\Datmusic\Logger;
use App\Datmusic\HttpClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $accounts = env('ACCOUNTS', null);
        if ($accounts != null && strlen($accounts) > 1) {
            $accounts = explode(',', $accounts);

            $accounts = array_map(function ($item) {
                return explode(':', $item);
            }, $accounts);

            config(['app.accounts' => $accounts]);
        }

        config(['app.proxy.enabled' => env('PROXY_ENABLE', false)]);
        config(['app.proxy.ip' => env('PROXY_IP', null)]);
        config(['app.proxy.port' => env('PROXY_PORT', null)]);
        config(['app.proxy.username' => env('PROXY_USERNAME', null)]);
        config(['app.proxy.password' => env('PROXY_PASSWORD', null)]);
        config(['app.proxy.method' => env('PROXY_METHOD', null)]);

        $httpClient = new HttpClient();
        $this->app->instance('httpClient', $httpClient);

        $logger = new Logger();
        $this->app->instance('logger', $logger);
    }
}
