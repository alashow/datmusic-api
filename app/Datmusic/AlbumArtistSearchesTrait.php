<?php
/**
 * Copyright (c) 2020  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait AlbumArtistSearchesTrait
{
    private $searchTypes = ['albums', 'artists'];

    private $getArtistTypes = ['audiosByArtist', 'albumsByArtist'];
    private $audiosByArtist = 'audiosByArtist';
    private $albumsByArtist = 'albumsByArtist';

    private $artistsSearchPrefix = 'artist:';
    private $albumSearchPrefix = 'album:';
    private $albumsSearchPrefix = 'albums:';
    private $albumsSearchLimit = 10;

    /**
     * @param Request $request
     * @param string  $query
     *
     * @return JsonResponse|bool
     */
    public function audiosByArtistName(Request $request, string $query)
    {
        $query = Str::replaceFirst($this->artistsSearchPrefix, '', $query);
        $artists = $this->searchArtists($request->merge(['q' => $query]))->getOriginalContent()['data'];

        logger()->searchBy('AudiosByArtistName', $query);

        if (! empty($artists)) {
            return $this->getArtistAudios($request, $artists[0]->id);
        } else {
            return false;
        }
    }

    /**
     * @param Request $request
     * @param string  $query
     *
     * @return JsonResponse|bool
     */
    public function audiosByAlbumName(Request $request, string $query)
    {
        $query = Str::replaceFirst($this->albumSearchPrefix, '', $query);
        $albums = $this->searchAlbums($request->merge(['q' => $query]))->getOriginalContent()['data'];

        logger()->searchBy('AudiosByAlbumName', $query);

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
     * @param Request $request
     * @param string  $query
     * @param int     $limit
     *
     * @return JsonResponse|bool
     */
    public function audiosByAlbumNameMultiple(Request $request, string $query, int $limit = 10)
    {
        $query = Str::replaceFirst($this->albumsSearchPrefix, '', $query);
        $albums = $this->searchAlbums($request->merge(['q' => $query]))->getOriginalContent()['data'];

        logger()->searchBy('AudiosByAlbumNameMultiple', $query);

        if (! empty($albums)) {
            $albums = collect($albums)->sortByDesc('plays')->take(min($limit, $this->albumsSearchLimit));

            return okResponse($albums->flatMap(function ($album) use ($request) {
                return $this->getAlbumById($request->merge([
                    'owner_id'   => $album->owner_id,
                    'access_key' => $album->access_key,
                ]), $album->id)->getOriginalContent()['data'];
            }));
        } else {
            return false;
        }
    }

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

        $error = $this->checkForErrors($request, $response);
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

        $query = getQuery($request);
        $offset = getPage($request) * $this->count;

        if (! is_null($cachedResult)) {
            logger()->searchByCache($type, $query, $offset);

            return okResponse($cachedResult);
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

        $error = $this->checkForErrors($request, $response);
        if ($error) {
            return $error;
        }

        $data = $response->response->items;
        $this->cacheResult($cacheKey, $data, $type);
        logger()->searchBy($type, $query, $offset);

        return okResponse($data);
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
        $offset = getPage($request) * $this->count;

        $cacheKey = sprintf('%s.%s', $type, $this->getCacheKeyForId($request, $artistId));
        $cachedResult = $this->getCache($cacheKey);

        if (! is_null($cachedResult)) {
            logger()->getArtistItemsCache($type, $artistId, $offset);

            return ! $isAudios ? okResponse($cachedResult) : $this->audiosResponse($request, $cachedResult, false);
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

        $error = $this->checkForErrors($request, $response);
        if ($error) {
            return $error;
        }

        $data = $isAudios ? $this->parseAudioItems($response) : $response->response->items;

        $this->cacheResult($cacheKey, $data);
        logger()->getArtistItems($type, $artistId, $offset);

        return $isAudios ? $this->audiosResponse($request, $data) : okResponse($data);
    }
}
