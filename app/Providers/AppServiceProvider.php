<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Providers;

use App\Util\CoverArtClient;
use App\Util\HttpClient;
use App\Util\Logger;
use Illuminate\Support\ServiceProvider;
use Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $tokens = env('AUTH_ACCESS_TOKENS', null);
        if ($tokens != null && strlen($tokens) > 1) {
            $tokens = explode(',', $tokens);
            config(['app.auth.tokens' => $tokens]);
        }

        // override CORs
        $origins = env('CORS_ALLOWED_ORIGINS', null);
        if ($origins != null && strlen($origins) > 1) {
            $origins = explode(',', $origins);
            config(['cors.allowedOrigins' => $origins]);
        }

        // register singletons
        $httpClient = new HttpClient();
        $this->app->instance('httpClient', $httpClient);

        $coverArtClient = new CoverArtClient();
        $this->app->instance('coverArtClient', $coverArtClient);

        $logger = new Logger();
        $this->app->instance('logger', $logger);

        // Create mp3s folders if they doesn't exist
        $this->createFolder(config('app.paths.mp3'));
        $this->createFolder(config('app.paths.links'));
    }

    /**
     * Creates given folder safely.
     *
     * @param $folder
     */
    private function createFolder($folder)
    {
        if (! @file_exists($folder)) {
            $created = mkdir($folder, 0777, true);
            if ($created) {
                Log::info(sprintf("Created folder '%s'", $folder));
            } else {
                Log::critical(sprintf("Folder doesn't exist and couldn't create it %s", $folder));
            }
        }
    }
}
