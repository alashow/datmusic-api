<?php
/*
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MeiliSearch\Client;

class MinervaConfigureMeilisearchCommand extends Command
{
    protected $signature = 'datmusic:minerva-meilisearch-configure';
    protected $description = 'Configures meilisearch index with default settings';

    /**
     * @return int status code
     */
    public function handle(): int
    {
        if (! config('app.minerva.meilisearch.enabled')) {
            $this->error('Minerva meilisearch is not enabled');

            return 1;
        }

        $client = new Client(config('app.minerva.meilisearch.url'), config('app.minerva.meilisearch.key'));
        $index = $client->index(config('app.minerva.meilisearch.index'));
        $index->updateRankingRules([
            'words', 'typo', 'proximity', 'attribute', 'exactness', // default
            'desc(created_at)',
            'desc(date)',
            'asc(album)',
        ]);

        return 0;
    }
}
