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

        $httpClient = new HttpClient();
        $this->app->instance('httpClient', $httpClient);

        $logger = new Logger();
        $this->app->instance('logger', $logger);
    }
}
