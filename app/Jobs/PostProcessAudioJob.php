<?php
/*
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Jobs;

use App\Datmusic\ParserTrait;
use GuzzleHttp\RequestOptions;

class PostProcessAudioJob extends Job
{
    protected $audioItems;
    protected $sinkUrl;

    use ParserTrait;

    public function __construct(...$items)
    {
        $this->sinkUrl = config('app.downloading.post_process.sink_url');
        $this->audioItems = array_map(function ($item) {
            return $this->cleanAudioItemForSink($item);
        }, $items);
    }

    public function handle()
    {
        // push audio items to sink url
        httpClient()->post($this->sinkUrl, [RequestOptions::JSON => [$this->audioItems]]);
    }
}
