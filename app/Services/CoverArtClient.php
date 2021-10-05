<?php
/*
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Services;

use App\Util\Scanner;
use Carbon\Carbon;
use Closure;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class CoverArtClient
{
    private $scanners = [];

    /**
     * @var Client Used for downloading cover files to set in mp3's.
     */
    private $coverDownloaderClient;

    /**
     * CoverArtClient constructor.
     */
    public function __construct($scanners)
    {
        $this->scanners = $scanners;

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
     * @param  array  $audio  audio item containing artist and title
     * @return bool|string
     */
    public function getCover(array $audio, $size)
    {
        $artist = $audio['artist'];
        $title = preg_replace('~(\(|\[|\{)[^)]*(\)|\]|\})~', '', $audio['title']);
        $cacheKey = sprintf('cover_%s', hash(config('app.hash.mp3'), sprintf('%s,%s', $artist, $title)));

        $retriever = function () use ($artist, $title) {
            foreach ($this->scanners as $scanner) {
                try {
                    return $scanner->findCover($artist, $title);
                } catch (\Exception $e) {
                    \Log::error('Exception while trying to find cover image.', [$e]);
                }
            }

            return false;
        };

        return $this->fetchCover($cacheKey, $retriever, $size);
    }

    /**
     * Get cover image file safely.
     * Returns path to cover image file if succeeds, false when fails.
     *
     * @param  array  $audio  audio item containing artist and title
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
                    $imageUrl = $this->getCover($audio, Scanner::$SIZE_LARGE);
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
     * Get artists image.
     *
     * @param  string  $artist
     * @param  string  $size
     * @return false|string
     */
    public function getArtistImage(string $artist, string $size)
    {
        $cacheKey = sprintf('artist_image_%s', hash(config('app.hash.mp3'), sprintf('%s', $artist)));

        $retriever = function () use ($artist) {
            foreach ($this->scanners as $scanner) {
                try {
                    return $scanner->findArtistImage($artist);
                } catch (\Exception $e) {
                    \Log::error('Exception while trying to find artist cover image.', [$e]);
                }
            }

            return false;
        };

        return $this->fetchCover($cacheKey, $retriever, $size, 15);
    }

    /**
     * @param  string  $cacheKey
     * @param  Closure  $retrieve
     * @param  string  $size
     * @param  int  $failureExpirationDays
     * @return string|false url or false if failed (both might be cached values)
     */
    private function fetchCover(string $cacheKey, Closure $retrieve, string $size, int $failureExpirationDays = 3)
    {
        $images = Cache::get($cacheKey);
        if (! $images) {
            if ($images === null) {
                $images = $retrieve();
                if (is_array($images)) {
                    Cache::forever($cacheKey, $images);
                    $url = $images[$size];
                    // force https
                    $url = preg_replace('/^http:/i', 'https:', $url);

                    return $url;
                } else {
                    // remember failure for n days
                    $expiresAt = Carbon::now()->addDays($failureExpirationDays);
                    Cache::put($cacheKey, false, $expiresAt);
                }
            }
        } else {
            if (is_array($images)) {
                return $images[$size];
            }
        }

        return false;
    }
}
