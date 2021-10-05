<?php
/*
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Services;

use App\Util\Scanner;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class CoverArtArchiveClient
{
    use Scanner;

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
     * @param  string  $artist
     * @param  string  $title
     * @return array|false size mapped images or false if fails
     */
    public function findCover(string $artist, string $title)
    {
        if ($releaseId = $this->getReleaseId($artist, $title)) {
            $response = $this->archiveClient->get('release/'.$releaseId);
            $response = json_decode($response->getBody());

            if (isset($response->images)) {
                foreach ($response->images as $cover) {
                    $thumbs = stdToArray($cover->thumbnails);

                    return [
                        self::$SIZE_LARGE  => $thumbs['1200'],
                        self::$SIZE_MEDIUM => $thumbs['500'],
                        self::$SIZE_SMALL  => $thumbs['250'],
                    ];
                }
            }
        }

        return false;
    }
}
