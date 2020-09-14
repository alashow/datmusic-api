<?php
/**
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait AlbumArtistSearchesTrait
{
    private $searchTypes = ['albums', 'artists'];

    private $getArtistTypes = ['audiosByArtist', 'albumsByArtist'];
    private $audiosByArtist = 'audiosByArtist';
    private $albumsByArtist = 'albumsByArtist';

    public function searchAlbums(Request $request)
    {
        return $this->searchItems($request, 'albums');
    }

    public function searchArtists(Request $request)
    {
        return $this->searchItems($request, 'artists');
    }

    public function getArtistAudios(Request $request, $artistId)
    {
        return $this->getArtistItems($request, $artistId, $this->audiosByArtist);
    }

    public function getArtistAlbums(Request $request, $artistId)
    {
        return $this->getArtistItems($request, $artistId, $this->albumsByArtist);
    }

    public function getAlbumById(Request $request, string $albumId)
    {
        $cacheKey = $this->getCacheKeyForId($request, $albumId);
        $cachedResult = $this->getCache($cacheKey);

        if (! is_null($cachedResult)) {
            logger()->getAlbumByIdCache($albumId);

            return $this->audiosResponse($request, $cachedResult, false);
        }

        $captchaParams = $this->getCaptchaParams($request);
        $params = [
            'access_token' => config('app.auth.tokens')[$this->accessTokenIndex],
            'album_id'     => $albumId,
            'count'        => $this->count,
            'owner_id'     => $request->get('owner_id'),
            'access_key'   => $request->get('access_key'),
        ];

        $response = as_json(httpClient()->get('method/audio.get', [
            'query' => $params + $captchaParams,
        ]
        ));

        $error = $this->checkForErrors($response);
        if ($error) {
            return $error;
        }

        $data = $this->parseAudioItems($response);
        $this->cacheResult($cacheKey, $data);
        logger()->getAlbumById($albumId);

        return $this->audiosResponse($request, $data);
    }

    /**
     * Search and cache mechanism for albums and artists.
     *
     * @param Request $request
     * @param string  $type
     *
     * @return JsonResponse
     */
    private function searchItems(Request $request, string $type)
    {
        if (! in_array($type, $this->searchTypes)) {
            abort(404);
        }

        $cacheKey = sprintf('%s.%s', $type, $this->getCacheKey($request));
        $cachedResult = $this->getCache($cacheKey, $type);

        $query = trim(getPossibleKeys($request, 'q', 'query'));
        $offset = abs(intval($request->get('page'))) * $this->count;

        if (! is_null($cachedResult)) {
            logger()->searchByCache($type, $query, $offset);

            return $this->ok($cachedResult);
        }

        $captchaParams = $this->getCaptchaParams($request);
        $params = [
            'access_token' => config('app.auth.tokens')[$this->accessTokenIndex],
            'q'            => $query,
            'offset'       => $offset,
            'count'        => $this->count,
        ];

        $response = as_json(httpClient()->get('method/audio.search'.ucfirst($type), [
            'query' => $params + $captchaParams,
        ]
        ));

        $error = $this->checkForErrors($response);
        if ($error) {
            return $error;
        }

        $data = $response->response->items;
        $this->cacheResult($cacheKey, $data, $type);
        logger()->searchBy($type, $query, $offset);

        return $this->ok($data);
    }

    /**
     * Search and cache mechanism for albums and artists.
     *
     * @param Request $request
     * @param string  $artistId
     * @param string  $type
     *
     * @return JsonResponse
     */
    private function getArtistItems(Request $request, string $artistId, string $type)
    {
        if (! in_array($type, $this->getArtistTypes)) {
            abort(404);
        }

        $isAudios = $type == $this->audiosByArtist;
        $offset = abs(intval($request->get('page'))) * $this->count;

        $cacheKey = sprintf('%s.%s', $type, $this->getCacheKeyForId($request, $artistId));
        $cachedResult = $this->getCache($cacheKey);

        if (! is_null($cachedResult)) {
            logger()->getArtistItemsCache($type, $artistId, $offset);

            return ! $isAudios ? $this->ok($cachedResult) : $this->audiosResponse($request, $cachedResult, false);
        }

        $captchaParams = $this->getCaptchaParams($request);
        $params = [
            'access_token' => config('app.auth.tokens')[$this->accessTokenIndex],
            'artist_id'    => $artistId,
            'offset'       => $offset,
            'count'        => $this->count,
            'extended'     => 1,
        ];

        $response = as_json(httpClient()->get('method/audio.get'.ucfirst($type), [
            'query' => $params + $captchaParams,
        ]
        ));

        $error = $this->checkForErrors($response);
        if ($error) {
            return $error;
        }

        $data = $isAudios ? $this->parseAudioItems($response) : $response->response->items;

        $this->cacheResult($cacheKey, $data);
        logger()->getArtistItems($type, $artistId, $offset);

        return $isAudios ? $this->audiosResponse($request, $data) : $this->ok($data);
    }
}
