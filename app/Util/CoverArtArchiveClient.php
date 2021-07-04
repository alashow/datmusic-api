<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Util;

use Concat\Http\Middleware\RateLimiter;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class CoverArtArchiveClient
{
    use CoverArtRetriever;

    /**
     * @var Client Guzzle client for cover archive
     */
    private $archiveClient;

    /**
     * @var Client Guzzle client for music brainz
     */
    private $brainzClient;

    /**
     * CoverArtArchiveClient constructor.
     */
    public function __construct()
    {
        $this->archiveClient = new Client([
            'base_uri' => 'https://coverartarchive.org',
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

    public function findCover(string $artist, string $title, string $size)
    {
        if ($releaseId = $this->getReleaseId($artist, $title)) {
            $response = $this->archiveClient->get('release/'.$releaseId);
            $response = json_decode($response->getBody());

            if (isset($response->images)) {
                foreach ($response->images as $cover) {
                    $thumbs = stdToArray($cover->thumbnails);
                    switch ($size) {
                        case self::$SIZE_LARGE:
                            return $thumbs['1200'];
                        case self::$SIZE_MEDIUM:
                            return $thumbs['500'];
                        case self::$SIZE_SMALL:
                            return $thumbs['250'];
                    }
                }
            }
        }
        return false;
    }
}
