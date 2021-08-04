<?php
/*
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Jobs;

use App\Datmusic\ParserTrait;
use App\Models\Audio;
use App\Util\Scanner;

class PostProcessAudioJob extends Job
{
    protected $audioItems;

    use ParserTrait;

    public function __construct(...$items)
    {
        $this->audioItems = array_map(function ($item) {
            return $this->cleanAudioItemForStorage($item);
        }, $items);
    }

    public function handle()
    {
        $items = $this->audioItems;

        if (! empty($items)) {
            if (config('app.minerva.fetch_covers')) {
                $items = array_map(function ($item) {
                    return $this->fetchCovers($item);
                }, $items);
            }

            if (config('app.minerva.database.enabled')) {
                try {
                    Audio::insertAudioItems($items);
                } catch (\Exception $exception) {
                    \Log::warning('Exception while inserting audio items to minerva', [$exception]);
                }
            }
        }
    }

    private function fetchCovers(array $audio)
    {
        // do nothing if it already has cover
        if (array_key_exists('cover_url', $audio)) {
            return $audio;
        }

        $coverUrl = covers()->getCover($audio, Scanner::$SIZE_LARGE);
        if ($coverUrl) {
            $audio['cover_url'] = $coverUrl;
            // we're assuming if it could find the large size, it can find the rest without failing
            $audio['cover_url_medium'] = covers()->getCover($audio, Scanner::$SIZE_MEDIUM);
            $audio['cover_url_small'] = covers()->getCover($audio, Scanner::$SIZE_SMALL);

            return $audio;
        } else {
            return $audio;
        } // failed to fetch the cover, just return what's given
    }
}
