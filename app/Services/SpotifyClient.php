<?php
/*
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Services;

use App\Util\Scanner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class SpotifyClient
{
    use Scanner;

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
        $expiration = Carbon::now()->addHour();

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
        if (empty($images)) {
            return false;
        }

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
        $results = $this->api->search($query, 'artist', ['limit' => 5])->artists;
        $resultCount = $results->total;
        if ($resultCount == 0) {
            return false;
        }
        // try to match by exact artist name if there are multiple results
        if ($resultCount > 1) {
            foreach ($results->items as $item) {
                if ($item->name == $artist) {
                    return $item;
                }
            }
        }
        // otherwise just return the first result
        return $results->items[0];
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

    public function findArtistImage(string $artist, string $size)
    {
        $artist = $this->findArtist($artist);
        if ($artist) {
            return $this->getImageBySize($artist->images, $size);
        } else {
            return false;
        }
    }
}
