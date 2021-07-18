<?php
/*
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Console\Commands;

use App\Models\Audio;
use Cache;
use Illuminate\Console\Command;
use MeiliSearch\Client;

class MinervaMeilisearchIndexCommand extends Command
{
    private static $LAST_INDEXED_AUDIO_CREATED_AT = 'minerva_last_indexed_audio';
    protected $signature = 'datmusic:minerva-meilisearch-index
                            {--reindex : Whether to index the whole database vs only the new items}';
    protected $description = 'Indexes meilisearch from minerva database';
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
        $reindex = $this->option('reindex');

        $audios = Audio::query()->oldest();
        if (! $reindex) {
            $lastAddedDate = Cache::get(self::$LAST_INDEXED_AUDIO_CREATED_AT);
            if ($lastAddedDate != null) {
                $audios = $audios->whereNotNull('created_at')->whereDate('created_at', '>', $lastAddedDate);
                if ($audios->count() == 0) {
                    $this->info('No recent items to index');

                    return 0;
                }
            } else {
                $this->error('Last indexed item is not known');

                return 1;
            }
        }

        $audios->chunk($this->batchCount, function ($audios) {
            $this->batchIndex($audios->toArray());
        });

        return 0;
    }

    private function batchIndex(array $audios)
    {
        if (empty($audios)) {
            $this->warn('No items to index');

            return;
        }

        $this->counter += count($audios);

        $this->info("Batch uploading {$this->batchCount} items to meilisearch, uploaded = {$this->counter}");
        $client = new Client(config('app.minerva.meilisearch.url'), config('app.minerva.meilisearch.key'));
        $index = $client->index(config('app.minerva.meilisearch.index'));
        $index->addDocuments($audios);

        $lastItem = $audios[array_key_last($audios)];
        if (array_key_exists('created_at', $lastItem)) {
            $lastDate = $lastItem['created_at'];
            if ($lastDate != null) {
                Cache::forever(self::$LAST_INDEXED_AUDIO_CREATED_AT, $lastDate);
            }
        }
    }
}
