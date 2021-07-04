<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Util;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class SpotifyClient
{
    use CoverArtRetriever;

    /**
     * @var SpotifyWebAPI instance.
     */
    private $api;

    /**
     * CoverArtArchiveClient constructor.
     */
    public function __construct()
    {
        $this->api = new SpotifyWebAPI();
        $this->api->setAccessToken($this->getAccessToken());
    }

    private function getAccessToken()
    {
        $expiration = Carbon::now()->addDays(7);

        return Cache::remember('spotify_access_token', $expiration, function () {
            $session = new Session(
                config('app.services.spotify.client_id'),
                config('app.services.spotify.client_secret')
            );

            $session->requestCredentialsToken();

            return $session->getAccessToken();
        });
    }

    private function getImageBySize($images, $size)
    {
        $image = null;
        switch ($size) {
            case self::$SIZE_LARGE:
                $image = $images[0];
                break;
            case self::$SIZE_MEDIUM:
                $image = $images[1];
                break;
            case self::$SIZE_SMALL:
                $image = $images[2];
                break;
        }

        return $image->url;
    }

    public function findArtist($artist)
    {
        $query = "artist:$artist";
        $result = $this->api->search($query, 'artist', ['limit' => 1])->artists;
        if ($result->total == 0) {
            return false;
        }

        return $result->items[0];
    }

    public function findCover($artist, $title, $size)
    {
        $query = "artist:$artist track:$title";
        $result = $this->api->search($query, 'track', ['limit' => 1])->tracks;
        if ($result->total == 0) {
            return false;
        }

        $images = $result->items[0]->album->images;

        return $this->getImageBySize($images, $size);
    }

    public function findArtistCover(string $artist, string $size)
    {
        $artist = $this->findArtist($artist);
        if ($artist) {
            return $this->getImageBySize($artist->images, $size);
        } else {
            return false;
        }
    }
}
