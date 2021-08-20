<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                $response = $this->search($request);
                break;
            case self::$SEARCH_BACKEND_ALBUMS:
                $response = $this->searchAlbums($request);
                break;
            case self::$SEARCH_BACKEND_ARTISTS:
                $response = $this->searchArtists($request);
                break;
            case self::$SEARCH_BACKEND_MINERVA:
                $response = $this->minervaSearch($request);
                break;
            case self::$SEARCH_BACKEND_DEEMIX:
                $response = $this->deemixSearch($request);
                break;
            default:
                abort('Unknown search backend type', 400);
        }
        $error = $this->checkResponseErrors($response);
        if ($error) {
            return $error;
        }
        $results = $response;
        if ($type != self::$SEARCH_BACKEND_AUDIOS) {
            $results = $this->pluckItems($response, $type);
        }

        return [$type => $results];
    }
}
