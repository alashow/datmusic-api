<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Util;

use Carbon\Carbon;
use Concat\Http\Middleware\RateLimiter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Cache;

class CoverArtClient
{

    private $retrievers = [];

    /**
     * @var Client Used for downloading cover files to set in mp3's.
     */
    private $coverDownloaderClient;

    /**
     * CoverArtClient constructor.
     */
    public function __construct(SpotifyClient $spotifyClient, CoverArtArchiveClient $coverArtArchiveClient)
    {
        if (config('app.services.spotify.enabled')) {
            $this->retrievers = [$spotifyClient, $coverArtArchiveClient];
        } else {
            $this->retrievers = [$coverArtArchiveClient];
        }

        $this->coverDownloaderClient = new Client([
            'headers' => [
                'User-Agent' => config('app.covers.user-agent'),
            ],
            'timeout' => 3,
        ]);
    }

    /**
     * Get cover image URL safely.
     * Returns full URL of cover image if succeeds, false when fails.
     *
     * @param array $audio audio item containing artist and title
     *
     * @return bool|string
     */
    public function getCover(array $audio, $size)
    {
        $artist = $audio['artist'];
        $title = preg_replace('~(\(|\[|\{)[^)]*(\)|\]|\})~', '', $audio['title']);
        $cacheKey = sprintf('cover_%s', hash(config('app.hash.mp3'), sprintf('%s,%s,%s', $artist, $title, $size)));

        $retrieve = function () use ($artist, $title, $size) {
            foreach ($this->retrievers as $retriever) {
                try {
                    return $retriever->findCover($artist, $title, $size);
                } catch (\Exception $e) {
                    \Log::error('Exception while trying to find cover image.', [$e]);
                }
            }
            return false;
        };

        if (! ($url = Cache::get($cacheKey))) {
            $url = $retrieve();
            if (is_string($url)) {
                // force https
                $url = preg_replace('/^http:/i', 'https:', $url);
                Cache::forever($cacheKey, $url);
            } else {
                // remember failure for n days
                $expiresAt = Carbon::now()->addDays(3);
                Cache::put($cacheKey, $url, $expiresAt);
            }
        }
        return $url;
    }

    /**
     * Get cover image file safely.
     * Returns path to cover image file if succeeds, false when fails.
     *
     * @param array $audio audio item containing artist and title
     *
     * @return bool|string
     */
    public function getCoverFile(array $audio)
    {
        try {
            if (array_key_exists('cover_url', $audio)) {
                $imageUrl = $audio['cover_url'];
                $client = vkClient();
            } else {
                if (config('app.downloading.id3.download_covers_external')) {
                    $imageUrl = $this->getCover($audio, CoverArtRetriever::$SIZE_LARGE);
                    $client = $this->coverDownloaderClient;
                } else {
                    return false;
                }
            }

            if ($imageUrl) {
                $imagePath = tempnam('/tmp', 'datmusic_cover_');
                $response = $client->get($imageUrl, [
                    'sink' => $imagePath,
                ]);

                if ($response->getStatusCode() == 200) {
                    return $imagePath;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Exception while trying to download cover image file', [$e]);
        }

        return false;
    }

    /**
     * Get artists photo.
     *
     * @param string $artist
     * @param string $size
     *
     * @return false|string
     */
    public function getArtistCover(string $artist, string $size)
    {
        $cacheKey = sprintf('artist_cover_%s', hash(config('app.hash.mp3'), sprintf('%s,%s', $artist, $size)));

        $retrieve = function () use ($artist, $size) {
            foreach ($this->retrievers as $retriever) {
                try {
                    return $retriever->findArtistCover($artist, $size);
                } catch (\Exception $e) {
                    \Log::error('Exception while trying to find artist cover image.', [$e]);
                }
            }
            return false;
        };

        if (! ($url = Cache::get($cacheKey))) {
            $url = $retrieve();
            if (is_string($url)) {
                Cache::forever($cacheKey, $url);
            } else {
                // remember failure for n days
                $expiresAt = Carbon::now()->addDays(15);
                Cache::put($cacheKey, $url, $expiresAt);
            }
        }
        return $url;
    }
}
