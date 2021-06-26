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
        $requestedTypes = $request->input('types', ['audios']);

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
            default:
                abort('Unknown search backend type', 400);
        }
        $error = $this->hasErrors($response);
        if ($error) {
            return $error;
        }
        $results = $response;
        if ($type != self::$SEARCH_BACKEND_AUDIOS) {
            $results = $this->pluckItems($response, $type);
        }

        return [$type => $results];
    }

    /**
     * Checks for errors in given responses.
     *
     * @param ...$responses
     *
     * @return false|JsonResponse first found error or false
     */
    public function hasErrors(...$responses)
    {
        foreach ($responses as $response) {
            if ($response instanceof JsonResponse) {
                if ($response->getOriginalContent()['status'] === 'error') {
                    return $response;
                }
            }
        }

        return false;
    }

    /**
     * Plucks given field key from response's data array.
     *
     * @param $response
     * @param $key
     *
     * @return false|array
     */
    public function pluckItems($response, $key)
    {
        if ($response instanceof JsonResponse) {
            return $response->getOriginalContent()['data'][$key];
        }

        return false;
    }
}
