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
    protected $audioItem;
    protected $subPath;
    protected $sinkUrl;

    use ParserTrait;

    public function __construct(array $audioItem)
    {
        $this->audioItem = $this->cleanAudioItemForSink($audioItem);
        $this->sinkUrl = config('app.downloading.post_process.sink_url');
    }

    public function handle()
    {
        // push the audio item to sink url
        httpClient()->post($this->sinkUrl, [RequestOptions::JSON => [$this->audioItem]]);
    }

    /**
     * For future use.
     */
    protected function archiveCoverArt()
    {
        if (config('app.downloading.post_process.archive_cover')) {
            $coverUrl = $this->audioItem['cover_url'];
            $outputPath = sprintf('%s/%s', config('app.paths.covers'), subPathForHash($this->audioItem['id']));
            mkdir($outputPath, 0777, true);
            $outputFile = sprintf('%s/%s.jpg', $outputPath, $this->audioItem['id']);
            if (! empty($coverUrl)) {
                httpClient()->get($coverUrl, [
                    'sink' => $outputFile,
                ]);
            }
        }
    }
}
