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

    /**
     * Get expiring cached access token.
     *
     * @return string
     */
    private function getAccessToken()
    {
        $expiration = Carbon::now()->addMinutes(50);

        return Cache::remember('spotify_access_token', $expiration, function () {
            $session = new Session(
                config('app.services.spotify.client_id'),
                config('app.services.spotify.client_secret')
            );

            $session->requestCredentialsToken();

            return $session->getAccessToken();
        });
    }

    private function getSizeMappedImages($images)
    {
        if (empty($images)) {
            return false;
        }

        return [
            self::$SIZE_LARGE  => $images[0]->url,
            self::$SIZE_MEDIUM => $images[1]->url,
            self::$SIZE_SMALL  => $images[2]->url,
        ];
    }

    /**
     * @param  string  $artist  artist name
     * @return false|array artist or false if fails
     */
    public function findArtist(string $artist)
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

    /**
     * @param  string  $artist  artist name
     * @param  string  $title  song title
     * @return array|false size mapped images or false if fails
     */
    public function findCover(string $artist, string $title)
    {
        $query = "artist:$artist track:$title";
        $result = $this->api->search($query, 'track', ['limit' => 1])->tracks;
        if ($result->total == 0) {
            return false;
        }

        $images = $result->items[0]->album->images;

        return $this->getSizeMappedImages($images);
    }

    /**
     * @param  string  $artist
     * @param  string  $size
     * @return array|false size mapped images or false if fails
     */
    public function findArtistImage(string $artist)
    {
        $artist = $this->findArtist($artist);
        if ($artist) {
            return $this->getSizeMappedImages($artist->images);
        } else {
            return false;
        }
    }
}
