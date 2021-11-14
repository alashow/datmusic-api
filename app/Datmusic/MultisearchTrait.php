<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPHtmlParser\Selector;

trait MultisearchTrait
{
    public function multisearch(Request $request)
    {
        $results = [];
        $requestedTypes = $request->input('types', '');
        if (! is_array($requestedTypes)) {
            $requestedTypes = ['audios'];
        }

        sort($requestedTypes);
        foreach (self::$SEARCH_BACKEND_TYPES as $type) {
            if (! in_array($type, $requestedTypes)) {
                continue;
            }

            $data = $this->searchBackend($request, $type);
            if ($data instanceof JsonResponse) {
                return $data;
            }
            array_push($results, $data);
        }

        logger()->searchMultisearch(getQuery($request), getPage($request) * $this->count, join(',', $requestedTypes));

        return okResponse(array_merge(...$results));
    }

    private function searchBackend(Request $request, $type)
    {
        $response = null;
        switch ($type) {
            case self::$SEARCH_BACKEND_AUDIOS:
            case self::$SEARCH_BACKEND_DEEMIX_FLACS:
                $response = $this->deemixSearch($request, $type);
                break;
            case self::$SEARCH_BACKEND_ALBUMS:
            case self::$SEARCH_BACKEND_DEEMIX_ALBUMS:
                $response = $this->deemixSearchAlbums($request);
                break;
            case self::$SEARCH_BACKEND_ARTISTS:
            case self::$SEARCH_BACKEND_DEEMIX_ARTISTS:
                $response = $this->deemixSearchArtists($request);
                break;
            case self::$SEARCH_BACKEND_MINERVA:
                $response = $this->minervaSearch($request);
                break;
            default:
                abort('Unknown search backend type', 400);
        }
        $error = $this->checkResponseErrors($response);
        if ($error) {
            return $error;
        }

        return [$type => $this->pluckItems($response, $type)];
    }
}
