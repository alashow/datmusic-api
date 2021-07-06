<?php
/*
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Console\Commands;

use App\Datmusic\CachesTrait;
use App\Datmusic\ParserTrait;
use GuzzleHttp\RequestOptions;
use Illuminate\Console\Command;

/**
 * Exports audio cache from given ids file.
 *
 * @category Console_Command
 */
class ExportAudioCacheCommand extends Command
{
    use CachesTrait, ParserTrait;

    protected $signature = 'datmusic:export-audio-from-cache
                            {audio-ids : path to audio ids file (id on every line)}';

    protected $description = '´Exports audios from cache to sink url';

    private $batchCount = 25000;
    private $counter = 0;

    /**
     * @return int status code
     */
    public function handle(): int
    {
        $foundAudios = [];
        $audioIdsPath = $this->argument('audio-ids');
        if (! @file_exists($audioIdsPath)) {
            $this->error("Audio ids file doesn't exist: $audioIdsPath");

            return 1;
        }

        $handle = fopen($audioIdsPath, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = str_replace(["\r", "\n"], '', $line);
                $audio = $this->getAudioById($line);
                if ($audio) {
                    array_push($foundAudios, $audio);
                    if (count($foundAudios) > $this->batchCount) {
                        $this->batchUpload($foundAudios);
                        $foundAudios = [];
                    }
                }
            }
            fclose($handle);
        } else {
            $this->warn("Couldn't open file: $audioIdsPath");
        }

        // upload the leftovers
        $this->batchUpload($foundAudios);

        return 0;
    }

    private function getAudioById($id)
    {
        $audioItem = $this->getCachedAudio($id);
        if ($audioItem && is_array($audioItem)) {
            return $this->cleanAudioItemForSink($audioItem);
        } else {
            $this->warn("Audio id = $id wasn't in cache");

            return false;
        }
    }

    private function batchUpload(array $audios)
    {
        if (empty($audios)) {
            $this->warn('No items to upload');

            return;
        }
        $this->counter += count($audios);
        $this->info("Batch uploading {$this->batchCount} items to sink, uploaded = {$this->counter}");
        httpClient()->post(config('app.downloading.post_process.sink_url'), [RequestOptions::JSON => $audios]);
    }
}
