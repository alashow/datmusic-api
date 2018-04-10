<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Providers;

use Log;
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
        // override CORs
        $origins = env('CORS_ALLOWED_ORIGINS', null);
        if ($origins != null && strlen($origins) > 1) {
            Log::info('Using CORS domains from .env');

            $origins = explode(',', $origins);
            config(['cors.allowedOrigins' => $origins]);
        }

        // register singletons
        $httpClient = new HttpClient();
        $this->app->instance('httpClient', $httpClient);

        $logger = new Logger();
        $this->app->instance('logger', $logger);

        // Create mp3s folder if it doesn't exist
        $mp3s = config('app.paths.mp3');
        if (! @file_exists($mp3s)) {
            $created = mkdir($mp3s, 0777, true);

            if ($created) {
                Log::info(sprintf("Created mp3s folder '%s'", $mp3s));
            } else {
                Log::critical(sprintf("Mp3 folder doesn't exist and couldn't create it %s", $mp3s));
            }
        }
    }
}
