<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\Request;
use MeiliSearch\Client;

trait MinervaSearchTrait
{
    public function minervaSearch(Request $request)
    {
        if (! config('app.minerva.meilisearch.enabled')) {
            abort(500, 'Meilisearch not enabled');
        }

        $query = getQuery($request);
        $pageBy = 50;
        $offset = getPage($request) * $pageBy;

        $client = new Client(config('app.minerva.meilisearch.url'), config('app.minerva.meilisearch.key'));
        $index = $client->index(config('app.minerva.meilisearch.index'));
        $results = $index->search($query, ['limit' => $pageBy, 'offset' => $offset]);

        $hits = $results->getHits();
        $hitsCount = $results->getNbHits();
        $tookMs = $results->getProcessingTimeMs();

        logger()->searchMinervaMeilisearch($query, $offset, $tookMs.'ms', 'count='.$hitsCount);

        $backendName = self::$SEARCH_BACKEND_MINERVA;

        return okResponse($this->cleanAudioList($request, $backendName, $hits, false), $backendName);
    }
}
