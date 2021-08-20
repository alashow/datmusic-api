<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Providers;

use App\Services\CoverArtArchiveClient;
use App\Services\CoverArtClient;
use App\Services\DeemixClient;
use App\Services\HttpClient;
use App\Services\SpotifyClient;
use App\Services\VkHttpClient;
use App\Util\Logger;
use Exception;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @throws Exception if tokens aren't setup
     */
    public function register()
    {
        $tokens = env('AUTH_ACCESS_TOKENS', null);
        if ($tokens != null && strlen($tokens) > 1) {
            $tokens = explode(',', $tokens);
            config(['app.auth.tokens' => $tokens]);
        } else {
            throw new Exception('No tokens found. Set tokens in .env to continue');
        }

        // override CORs
        $origins = env('CORS_ALLOWED_ORIGINS', null);
        if ($origins != null && strlen($origins) > 1) {
            $origins = explode(',', $origins);
            config(['cors.allowed_origins' => $origins]);
        }

        $this->registerSingletons();

        // Create mp3s folders if they doesn't exist
        $this->createFolder(config('app.paths.mp3'));
        $this->createFolder(config('app.paths.links'));

        URL::forceRootUrl(env('APP_URL'));
    }

    private function registerSingletons()
    {
        $vkClient = new VkHttpClient();
        $this->app->instance('vkClient', $vkClient);

        $httpClient = new HttpClient();
        $this->app->instance('httpClient', $httpClient);

        if (config('app.deemix.enabled')) {
            $deemixClient = new DeemixClient();
            $this->app->instance('deemixClient', $deemixClient);
        }

        $scanners = [];

        if (config('app.services.spotify.enabled')) {
            $spotifyClient = new SpotifyClient();
            $this->app->instance('spotifyClient', $spotifyClient);
            array_push($scanners, $spotifyClient);
        }

        $coverArtArchiveClient = new CoverArtArchiveClient();
        $this->app->instance('coverArtArchiveClient', $coverArtArchiveClient);
        array_push($scanners, $coverArtArchiveClient);

        $this->app->instance('scanners', $scanners);

        $coverArtClient = new CoverArtClient($scanners);
        $this->app->instance('coverArtClient', $coverArtClient);

        $logger = new Logger();
        $this->app->instance('logger', $logger);
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
