<?php
/*
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Console\Commands;

use App\Models\Audio;
use Illuminate\Console\Command;
use MeiliSearch\Client;

class MinervaMeilisearchReindexCommand extends Command
{
    protected $signature = 'datmusic:minerva-meilisearch-reindex';

    protected $description = 'Reindexes meilisearch index from minerva database';

    private $batchCount = 10000;
    private $counter = 0;

    /**
     * @return int status code
     */
    public function handle(): int
    {
        if (! config('app.minerva.meilisearch.enabled')) {
            $this->error('Minerva meilisearch is not enabled');

            return 1;
        }
        if (! config('app.minerva.database.enabled')) {
            $this->error('Minerva database is not enabled');

            return 1;
        }

        Audio::chunk($this->batchCount, function ($audios) {
            $this->batchIndex($audios->toArray());
        });

        return 0;
    }

    private function batchIndex(array $audios)
    {
        if (empty($audios)) {
            $this->warn('No items to export');

            return;
        }

        $this->counter += count($audios);

        $this->info("Batch uploading {$this->batchCount} items to meilisearch, uploaded = {$this->counter}");
        $client = new Client(config('app.minerva.meilisearch.url'), config('app.minerva.meilisearch.key'));
        $index = $client->index(config('app.minerva.meilisearch.index'));
        $index->addDocuments($audios);
    }
}
