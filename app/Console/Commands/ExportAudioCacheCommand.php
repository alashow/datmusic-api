<?php
/*
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Console\Commands;

use App\Datmusic\CachesTrait;
use App\Datmusic\ParserTrait;
use App\Models\Audio;
use Illuminate\Console\Command;

class ExportAudioCacheCommand extends Command
{
    use CachesTrait, ParserTrait;

    protected $signature = 'datmusic:export-audios-from-cache
                            {audio-ids : path to audio ids file (id on every line)}';

    protected $description = 'Exports audios from cache to minerva';

    private $batchCount = 10000;
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
                        $this->batchExport($foundAudios);
                        $foundAudios = [];
                    }
                }
            }
            fclose($handle);
        } else {
            $this->warn("Couldn't open file: $audioIdsPath");
        }

        // export the leftovers
        $this->batchExport($foundAudios);

        return 0;
    }

    private function getAudioById($id)
    {
        $audioItem = $this->getCachedAudio($id);
        if ($audioItem && is_array($audioItem)) {
            return $this->cleanAudioItemForStorage($audioItem);
        } else {
            $this->warn("Audio id = '$id' wasn't in cache");

            return false;
        }
    }

    private function batchExport(array $audios)
    {
        if (empty($audios)) {
            $this->warn('No items to export');

            return;
        }

        $this->counter += count($audios);

        if (config('app.minerva.database.enabled')) {
            $this->info("Batch inserting {$this->batchCount} items to minerva database, inserted = {$this->counter}");
            try {
                Audio::insertAudioItems($audios);
            } catch (\Exception $exception) {
                $this->error('Error while bulk inserting items: '.$exception->getMessage());
            }
        }
    }
}
