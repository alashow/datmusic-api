<?php
/**
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait AlbumArtistSearchesTrait
{
    private $getArtistTypes = ['byId', 'artistById', 'audiosByArtist', 'albumsByArtist'];
    private $audioById = 'byId';
    private $artistById = 'artistById';
    private $audiosByArtist = 'audiosByArtist';
    private $albumsByArtist = 'albumsByArtist';

    private $artistsSearchPrefix = 'artist:';
    private $albumSearchPrefix = 'album:';
    private $albumsSearchPrefix = 'albums:';
    private $albumsSearchLimit = 10;

    private function artistTypeToSearchBackend($type)
    {
        switch ($type) {
            case $this->artistById:
                return self::$SEARCH_BACKEND_ARTIST;
            case $this->audiosByArtist:
                return self::$SEARCH_BACKEND_AUDIOS;
            case $this->albumsByArtist:
                return self::$SEARCH_BACKEND_ALBUMS;
            default:
                abort('Unknown type', '400');
        }
    }

    public function getArtist(Request $request, $id)
    {
        $results = [];
        foreach ([$this->audiosByArtist, $this->albumsByArtist] as $type) {
            $response = $this->getArtistItems($request, $id, $type);
            $error = $this->checkResponseErrors($response);
            if ($error) {
                return $error;
            }
            $backendType = $this->artistTypeToSearchBackend($type);
            $results[$backendType] = $this->pluckItems($response, $backendType);
        }

        // try to include artist details from albums
        try {
            $artistDetails = stdToArray(collect($results[self::$SEARCH_BACKEND_ALBUMS])->take(10)->pluck('main_artists')->flatten()->firstWhere('id', $id)) ?: [];
        } catch (Exception $e) { // albums can be empty on after the first page
            $artistDetails = [];
        }

        return okResponse(['artist' => array_merge($artistDetails, $results)]);
    }

    public function searchAlbums(Request $request)
    {
        return $this->searchItems($request, self::$SEARCH_BACKEND_ALBUMS);
    }

    public function searchArtists(Request $request)
    {
        return $this->searchItems($request, self::$SEARCH_BACKEND_ARTISTS);
    }

    public function getArtistAudios(Request $request, $id)
    {
        return $this->getArtistItems($request, $id, $this->audiosByArtist);
    }

    public function getArtistAlbums(Request $request, $id)
    {
        return $this->getArtistItems($request, $id, $this->albumsByArtist);
    }

    public function getAlbumById(Request $request, string $id)
    {
        $cacheKey = $this->getCacheKeyForId($request, $id);
        $cachedResult = $this->getCache($cacheKey);

        if (! is_null($cachedResult)) {
            logger()->getAlbumByIdCache($id);

            return $this->audiosResponse($request, $cachedResult, false);
        }

        $captchaParams = $this->getCaptchaParams($request);
        $params = [
            'access_token' => config('app.auth.tokens')[$this->accessTokenIndex],
            'album_id'     => $id,
            'count'        => $this->count,
            'owner_id'     => $request->input('owner_id'),
            'access_key'   => $request->input('access_key'),
        ];

        $response = as_json(vkClient()->get('method/audio.get', [
            'query' => $params + $captchaParams,
        ]
        ));

        $error = $this->checkSearchResponseError($request, $response);
        if ($error) {
            return $error;
        }

        $data = $this->parseAudioItems($response->response->items);
        $this->cacheResult($cacheKey, $data);
        logger()->getAlbumById($id);

        return $this->audiosResponse($request, $data);
    }

    /**
     * Search and cache mechanism for albums and artists.
     *
     * @param  Request  $request
     * @param  string  $type
     * @return JsonResponse|array
     */
    private function searchItems(Request $request, string $type)
    {
        if (! in_array($type, [self::$SEARCH_BACKEND_ALBUMS, self::$SEARCH_BACKEND_ARTISTS])) {
            abort(404);
        }

        $cacheKey = sprintf('%s.%s', $type, $this->getCacheKey($request));
        $cachedResult = $this->getCache($cacheKey, $type);

        $query = getQuery($request);
        $offset = getPage($request) * $this->count;

        if (! is_null($cachedResult)) {
            logger()->searchByCache($type, $query, $offset);

            return okResponse($cachedResult, $type);
        }

        $captchaParams = $this->getCaptchaParams($request);
        $params = [
            'access_token' => config('app.auth.tokens')[$this->accessTokenIndex],
            'q'            => $query,
            'offset'       => $offset,
            'count'        => $this->count,
        ];

        $response = as_json(vkClient()->get('method/audio.search'.ucfirst($type), [
            'query' => $params + $captchaParams,
        ]
        ));

        $error = $this->checkSearchResponseError($request, $response);
        if ($error) {
            return $error;
        }

        $data = $response->response->items;
        $this->cacheResult($cacheKey, $data, $type);
        logger()->searchBy($type, $query, $offset);

        return okResponse($data, $type);
    }

    /**
     * Search and cache mechanism for albums and artists.
     *
     * @param  Request  $request
     * @param  string  $artistId
     * @param  string  $type
     * @return JsonResponse|array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getArtistItems(Request $request, string $artistId, string $type)
    {
        if (! in_array($type, $this->getArtistTypes)) {
            abort(404);
        }

        $isArtistById = $type == $this->artistById;
        $isAudiosByArtist = $type == $this->audiosByArtist;
        $dataFieldName = $this->artistTypeToSearchBackend($type);

        $offset = getPage($request) * $this->count;

        $cacheKey = sprintf('%s.%s', $type, $this->getCacheKeyForId($request, $artistId));
        $cachedResult = $this->getCache($cacheKey);

        if (! is_null($cachedResult)) {
            logger()->getArtistItemsCache($type, $artistId, $offset);

            return $isAudiosByArtist ? $this->audiosResponse($request, $cachedResult, false) : okResponse($cachedResult, $dataFieldName);
        }

        $captchaParams = $this->getCaptchaParams($request);
        $params = [
            'access_token' => config('app.auth.tokens')[$this->accessTokenIndex],
            'artist_id'    => $artistId,
            'offset'       => $offset,
            'count'        => $this->count,
            'extended'     => 1,
        ];

        $response = as_json(vkClient()->get('method/audio.get'.ucfirst($type), [
            'query' => $params + $captchaParams,
        ]
        ));

        $error = $this->checkSearchResponseError($request, $response);
        if ($error) {
            return $error;
        }

        $data = $isAudiosByArtist ? $this->parseAudioItems($response->response->items)
            : ($isArtistById ? $response->response : $response->response->items);
        $this->cacheResult($cacheKey, $data);
        logger()->getArtistItems($type, $artistId, $offset);

        return $isAudiosByArtist ? $this->audiosResponse($request, $data) : okResponse($data, $dataFieldName);
    }

    /**
     * Search and cache mechanism for albums and artists.
     *
     * @param  Request  $request
     * @param  array  ...$audios
     * @return JsonResponse|array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getAudios(Request $request, ...$audios)
    {
        $audios = array_map(function ($item) {
            return $item['source_id'];
        }, $audios);
        $captchaParams = $this->getCaptchaParams($request);
        $params = [
            'access_token' => config('app.auth.tokens')[$this->accessTokenIndex],
            'audios'       => $audios,
            'extended'     => 1,
        ];

        $response = as_json(vkClient()->get('method/audio.getById', [
            'query' => $params + $captchaParams,
        ]
        ));

        $error = $this->checkSearchResponseError($request, $response);
        if ($error) {
            return $error;
        }

        return $this->parseAudioItems($response->response);
    }

    /**
     * @param  Request  $request
     * @param  string  $query
     * @return JsonResponse|bool
     */
    public function audiosByArtistName(Request $request, string $query)
    {
        $query = Str::replaceFirst($this->artistsSearchPrefix, '', $query);
        $artists = $this->searchArtists($request->merge(['q' => $query]))->getOriginalContent()['data'][self::$SEARCH_BACKEND_ARTISTS];

        logger()->searchBy('AudiosByArtistName', $query, 'Account#'.$this->accessTokenIndex, 'count='.count($artists));

        if (! empty($artists)) {
            return $this->getArtistAudios($request, $artists[0]->id);
        } else {
            return false;
        }
    }

    /**
     * @param  Request  $request
     * @param  string  $query
     * @return JsonResponse|bool
     */
    public function audiosByAlbumName(Request $request, string $query)
    {
        $query = Str::replaceFirst($this->albumSearchPrefix, '', $query);
        $albums = $this->searchAlbums($request->merge(['q' => $query]))->getOriginalContent()['data'][self::$SEARCH_BACKEND_ALBUMS];

        logger()->searchBy('AudiosByAlbumName', $query, 'Account#'.$this->accessTokenIndex, 'count='.count($albums));

        if (! empty($albums)) {
            $album = collect($albums)->sortByDesc('plays')->first();

            return $this->getAlbumById($request->merge([
                'owner_id'   => $album->owner_id,
                'access_key' => $album->access_key,
            ]), $album->id);
        } else {
            return false;
        }
    }

    /**
     * @param  Request  $request
     * @param  string  $query
     * @param  int  $limit
     * @return JsonResponse|bool
     */
    public function audiosByAlbumNameMultiple(Request $request, string $query, int $limit = 10)
    {
        $query = Str::replaceFirst($this->albumsSearchPrefix, '', $query);
        $albums = $this->searchAlbums($request->merge(['q' => $query]))->getOriginalContent()['data'][self::$SEARCH_BACKEND_ALBUMS];

        logger()->searchBy('AudiosByAlbumNameMultiple', $query, 'Account#'.$this->accessTokenIndex, 'count='.count($albums));

        if (! empty($albums)) {
            $albums = collect($albums)->sortByDesc('plays')->take(min($limit, $this->albumsSearchLimit));

            return okResponse($albums->flatMap(function ($album) use ($request) {
                return $this->getAlbumById($request->merge([
                    'owner_id'   => $album->owner_id,
                    'access_key' => $album->access_key,
                ]), $album->id)->getOriginalContent()['data']['audios'];
            })->toArray(), 'audios');
        } else {
            return false;
        }
    }
}
