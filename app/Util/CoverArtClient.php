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
    /**
     * @var Client Guzzle client for cover archive
     */
    private $archiveClient;

    /**
     * @var Client Guzzle client for music brainz
     */
    private $brainzClient;

    /**
     * CoverArtClient constructor.
     */
    public function __construct()
    {
        $this->archiveClient = new Client([
            'base_uri' => 'http://coverartarchive.org',
            'headers'  => [
                'User-Agent' => config('app.covers.user-agent'),
            ],
            'timeout'  => 3,
        ]);

        $handler = HandlerStack::create();
        $rateLimitProvider = new MusicBrainzRateLimitProvider();
        $handler->push(new RateLimiter($rateLimitProvider));
        $this->brainzClient = new Client([
            'base_uri' => 'https://musicbrainz.org',
            'headers'  => [
                'User-Agent' => config('app.covers.user-agent'),
            ],
            'timeout'  => 3,
            'handler'  => $handler,
        ]);
    }

    /**
     * Searches for MusicBrainz release id for given audio artist and title.
     *
     * @param $artist
     * @param $title
     *
     * @return bool|string release id if succeeds, false when fails.
     */
    private function getReleaseId($artist, $title)
    {
        $response = $this->brainzClient->get('ws/2/recording', [
            'query' => [
                'query' => sprintf('artist:%s AND recording:%s', $artist, $title),
                'limit' => '1',
                'fmt'   => 'json',
            ],
        ]);
        $response = json_decode($response->getBody());

        if (isset($response->recordings)) {
            foreach ($response->recordings as $record) {
                if (isset($record->releases)) {
                    foreach ($record->releases as $release) {
                        return $release->id;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get cover image URL safely.
     * Returns full URL of cover image if succeeds, false when fails.
     *
     * @param array $audio audio item containing artist and title
     *
     * @return bool|string
     */
    public function getImage(array $audio)
    {
        $artist = $audio['artist'];
        $title = preg_replace('~(\(|\[|\{)[^)]*(\)|\]|\})~', '', $audio['title']);
        $cacheKey = sprintf('cover_%s', hash(config('app.hash.mp3'), sprintf('%s,%s', $artist, $title)));

        $retrieve = function () use ($artist, $title) {
            try {
                if ($releaseId = $this->getReleaseId($artist, $title)) {
                    $response = $this->archiveClient->get('release/'.$releaseId);
                    $response = json_decode($response->getBody());

                    if (isset($response->images)) {
                        foreach ($response->images as $cover) {
                            return $cover->thumbnails->large;
                        }
                    }
                }
            } catch (\Exception $e) {
                if (! $e instanceof ClientException) {
                    \Log::error('Exception while trying to fetch cover image. Will try to fetch from Amazon', [$e]);
                }
            }

            return false;
        };

        if ($value = Cache::get($cacheKey)) {
            return $value;
        } else {
            $value = $retrieve();
            $expiresAt = $value ? Carbon::now()->addWeek(1) : Carbon::now()->addDays(1);
            Cache::put($cacheKey, $value, $expiresAt);

            return $value;
        }
    }

    /**
     * Get cover image file safely.
     * Returns path to cover image file if succeeds, false when fails.
     *
     * @param array $audio audio item containing artist and title
     *
     * @return bool|string
     */
    public function getImageFile(array $audio)
    {
        try {
            if (array_key_exists('cover_url', $audio)) {
                $imageUrl = $audio['cover_url'];
                $client = httpClient();
            } elseif (config('app.downloading.id3.download_covers_external')) {
                $imageUrl = $this->getImage($audio);
                $client = $this->archiveClient;
            } else {
                return false;
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
}
