<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use App\Models\Audio;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Lumen\Http\Redirector;
use stdClass;

trait DeemixTrait
{
    private function verifyDeemixEnabled()
    {
        if (! config('app.deemix.enabled') || ! config('app.minerva.database.enabled')) {
            abort(500, 'Deemix or minerva not enabled');
        }
    }

    public function isDeemixId($id)
    {
        return Str::startsWith($id, Audio::$DEEMIX_ID_PREFIX);
    }

    public function deemixSearchAudios(Request $request)
    {
        return $this->deemixSearch($request, self::$SEARCH_BACKEND_AUDIOS);
    }

    public function deemixSearchFlacs(Request $request)
    {
        return $this->deemixSearch($request, self::$SEARCH_BACKEND_DEEMIX_FLACS);
    }

    public function deemixSearchArtists(Request $request)
    {
        return $this->deemixSearch($request, self::$SEARCH_BACKEND_DEEMIX_ARTISTS);
    }

    public function deemixSearchAlbums(Request $request)
    {
        return $this->deemixSearch($request, self::$SEARCH_BACKEND_DEEMIX_ALBUMS);
    }

    public function deemixSearch(Request $request, $backendName)
    {
        $this->verifyDeemixEnabled();

        $query = getQuery($request);
        $pageBy = 50;
        $offset = getPage($request) * $pageBy;

        $isAudios = $backendName == self::$SEARCH_BACKEND_AUDIOS || $backendName == self::$SEARCH_BACKEND_DEEMIX_FLACS;
        if ($isAudios) {
            if (empty($query)) {
                $query = randomQuery();
            }
        }

        $cacheKey = $this->getCacheKey($request);
        $cachedResult = $this->getCache($cacheKey, $backendName);
        $isCachedQuery = ! is_null($cachedResult);

        if ($isCachedQuery) {
            logger()->searchByCache($backendName, $query, $offset, 'count='.count($cachedResult));

            return okResponse($isAudios ? $this->cleanAudioList($request, $backendName, $cachedResult) : $cachedResult, $backendName);
        }

        $searchEndpoint = 'search';
        switch ($backendName) {
            case self::$SEARCH_BACKEND_DEEMIX_ALBUMS:
                $searchEndpoint = 'search/albums';
                break;
            case self::$SEARCH_BACKEND_DEEMIX_ARTISTS:
                $searchEndpoint = 'search/artists';
                break;
        }

        $response = json_decode(deemixClient()->get($searchEndpoint, [
            'query' => [
                'q'      => $query,
                'offset' => $offset,
                'limit'  => $pageBy,
            ],
        ])->getBody());

        $result = $this->mapDeemixSearchResults($response->data, $backendName);
        $hitsCount = $response->total;

        $this->cacheResult($cacheKey, $result, $backendName);
        logger()->searchBy($backendName, $query, $offset, 'count='.$hitsCount);

        return okResponse($isAudios ? $this->cleanAudioList($request, $backendName, $result, false) : $result, $backendName);
    }

    /**
     * @param  Request  $request
     * @param  string  $id
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function deemixArtist(Request $request, string $id)
    {
        $this->verifyDeemixEnabled();
        $backendName = self::$SEARCH_BACKEND_ARTIST;

        $cacheKey = $this->getCacheKeyForId($request, $id);
        $cachedResult = $this->getCache($cacheKey, $backendName);
        $isCachedQuery = ! is_null($cachedResult);

        if ($isCachedQuery) {
            logger()->getArtistItemsCache('artist', $id, 'count='.count($cachedResult));

            return okResponse($cachedResult, $backendName);
        }

        try {
            $response = json_decode(deemixClient()->get('artists/'.$id)->getBody());
        } catch (GuzzleException $e) {
            logger()->getArtistItemsCache('artist', $id, 'error='.$e->getMessage());
            abort(404);
        }

        $result = $this->mapDeemixArtist($response);

        $this->cacheResult($cacheKey, $result, $backendName);
        logger()->getArtistItemsCache('artist', $id, 'count='.count($result));

        return okResponse($result, $backendName);
    }

    public function deemixArtistAudios(Request $request, string $id)
    {
        $response = $this->deemixArtist($request, $id);
        $audios = $response->getData()->data->artist->audios;

        return okResponse($audios, self::$SEARCH_BACKEND_AUDIOS);
    }

    public function deemixArtistAlbums(Request $request, string $id)
    {
        $response = $this->deemixArtist($request, $id);
        $albums = $response->getData()->data->artist->albums;

        return okResponse($albums, self::$SEARCH_BACKEND_ALBUMS);
    }

    /**
     * @param  Request  $request
     * @param  string  $id
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function deemixAlbum(Request $request, string $id)
    {
        $this->verifyDeemixEnabled();
        $backendName = self::$SEARCH_BACKEND_ALBUM;

        $cacheKey = $this->getCacheKeyForId($request, $id);
        $cachedResult = $this->getCache($cacheKey, $backendName);
        $isCachedQuery = ! is_null($cachedResult);

        if ($isCachedQuery) {
            logger()->getArtistItemsCache('album', $id, 'count='.count($cachedResult));

            return okResponse($cachedResult);
        }

        try {
            $response = json_decode(deemixClient()->get('albums/'.$id)->getBody());
        } catch (GuzzleException $e) {
            logger()->getArtistItemsCache('album', $id, 'error='.$e->getMessage());
            abort(404);

            return null;
        }

        $result = $this->mapDeemixAlbum($response);
        $audios = $result['audios'];
        unset($result['audios']);
        $result = [
            'album'  => $result,
            'audios' => $audios,
        ];

        $this->cacheResult($cacheKey, $result, $backendName);
        logger()->getArtistItemsCache('albums', $id, 'count='.count($result));

        return okResponse($result);
    }

    /**
     * Downloads audio via Deemix and redirects to downloaded file.
     *
     * @param  Request  $request
     * @param  string  $key
     * @param  string  $id
     * @param  bool  $stream
     * @return RedirectResponse
     *
     * @throws GuzzleException
     */
    public function deemixDownload(Request $request, string $key, string $id, bool $stream = false)
    {
        $shouldVerifyDownloadFiles = config('app.deemix.downloads_verify_file_before_redirect');
        $this->verifyDeemixEnabled();
        $bitrate = $key == self::$SEARCH_BACKEND_DEEMIX_FLACS ? 'flac' : 'mp3';

        $cached = Audio::findDeemix($id, $bitrate);
        if (! is_null($cached)) {
            if (! $shouldVerifyDownloadFiles || (@file_exists($cached->source_id))) {
                return $this->deemixDownloadResponse($stream, true, $id, $cached->source_id);
            }
        }

        $trackId = str_replace(Audio::$DEEMIX_ID_PREFIX, '', $id);
        $dlPath = sprintf('dl/track/%s/%s', $trackId, $bitrate);

        $response = json_decode(deemixDownloaderClient()->get($dlPath)->getBody());
        $hasErrors = ! empty($response->errors);

        if (! $hasErrors) {
            $result = $this->mapDeemixDownloadResult($response);
            $this->onDownloadCallback($result);

            return $this->deemixDownloadResponse($stream, false, $id, $result['source_id']);
        }

        logger()->log('Download.Fail.Deemix', json_encode($response->errors));

        abort(500);

        return null;
    }

    /**
     * Redirects to deemix download.
     *
     * @param $isStream
     * @param $isCache
     * @param $id
     * @param $path
     * @return RedirectResponse|Redirector
     */
    private function deemixDownloadResponse($isStream, $isCache, $id, $path)
    {
        $downloadsFolder = config('app.deemix.downloads_folder');
        $downloadsRewrite = config('app.deemix.downloads_folder_rewrite');
        if ($downloadsRewrite != null) {
            $downloadsFolderReg = '/'.preg_quote($downloadsFolder, '/').'/';
            $path = preg_replace($downloadsFolderReg, $downloadsRewrite, $path, 1);
        }
        logger()->deemixDownload($isStream, $isCache, $id, $path);

        return redirect($path);
    }

    /**
     * @param  array  $data
     * @param  string  $backend
     * @param  stdClass|null  $artist
     * @param  stdClass|null  $album
     * @return array[]
     *
     * @throws Exception if unknown $backend
     */
    private function mapDeemixSearchResults(array $data, string $backend, stdClass $artist = null, stdClass $album = null)
    {
        return array_map(function ($item) use ($backend, $artist, $album) {
            switch ($backend) {
                case self::$SEARCH_BACKEND_AUDIOS:
                case self::$SEARCH_BACKEND_DEEMIX_FLACS:
                    return $this->mapDeemixTrack($item, $album);
                case self::$SEARCH_BACKEND_ARTISTS:
                case self::$SEARCH_BACKEND_DEEMIX_ARTISTS:
                    return $this->mapDeemixArtist($item);
                case self::$SEARCH_BACKEND_ALBUMS:
                case self::$SEARCH_BACKEND_DEEMIX_ALBUMS:
                    return $this->mapDeemixAlbum($item, $artist);
                default:
                    throw new Exception("Unknown type: $backend");
            }
        }, $data);
    }

    /**
     * Map deemix track to datmusic audio.
     *
     * @param $item
     * @return array
     */
    private function mapDeemixTrack($item, stdClass $album = null): array
    {
        $album = $album ?: $item->album;

        return [
            'id'               => Audio::$DEEMIX_ID_PREFIX.$item->id,
            'source_id'        => strval($item->id),
            'title'            => $item->title,
            'artist'           => $item->artist->name,
            'duration'         => $item->duration,
            'is_explicit'      => $item->explicit_lyrics,
            'album'            => $album->title,
            'cover_url'        => $album->cover_xl,
            'cover_url_medium' => $album->cover_big,
            'cover_url_small'  => $album->cover_medium,
        ];
    }

    /**
     * Map deemix downloaded track to datmusic audio.
     *
     * @param $data
     * @return array
     */
    private function mapDeemixDownloadResult($data)
    {
        $trackInfo = $data->single->trackAPI;

        return [
            'id'               => Audio::$DEEMIX_ID_PREFIX.$data->id,
            'source_id'        => $data->files[0]->path,
            'title'            => $data->title,
            'artist'           => $data->artist,
            'album'            => $trackInfo->album->title,
            'cover_url'        => $trackInfo->album->cover,
            'cover_url_medium' => $trackInfo->album->cover_big,
            'cover_url_small'  => $trackInfo->album->cover_medium,
            'duration'         => intval($trackInfo->duration),
            'is_explicit'      => $data->explicit,
            'date'             => Carbon::parse($trackInfo->release_date)->timestamp,
            'extra_info'       => json_encode($trackInfo),
        ];
    }

    /**
     * Map deemix artist to datmusic artist.
     *
     * @param  stdClass  $item
     * @return array
     *
     * @throws Exception
     */
    private function mapDeemixArtist(stdClass $item)
    {
        $audios = property_exists($item, 'top') ? $this->cleanAudioList(app('request'), self::$SEARCH_BACKEND_AUDIOS, $this->mapDeemixSearchResults($item->top->data, self::$SEARCH_BACKEND_AUDIOS), false) : [];
        $albums = property_exists($item, 'albums') ? $this->mapDeemixSearchResults($item->albums->data, self::$SEARCH_BACKEND_DEEMIX_ALBUMS, $item) : [];

        $hasPhoto = preg_match("/images\/artist\/([a-zA-Z0-9-.]{1,32}\/?){2}/", $item->picture_xl) == 1;
        $buildArtistPhoto = function () use ($item) {
            return [
                [
                    'url'    => $item->picture_xl,
                    'width'  => 1000,
                    'height' => 1000,
                ],
                [
                    'url'    => $item->picture_big,
                    'width'  => 500,
                    'height' => 500,
                ],
                [
                    'url'    => $item->picture_medium,
                    'width'  => 250,
                    'height' => 250,
                ],
            ];
        };

        return [
            'id'          => strval($item->id),
            'name'        => $item->name,
            'fans'        => safeProp($item, 'nb_fan') ?: 0,
            'album_count' => safeProp($item, 'nb_album') ?: 0,
            'photo'       => $hasPhoto ? $buildArtistPhoto() : [],
            'audios'      => $audios,
            'albums'      => $albums,
        ];
    }

    /**
     * Map deemix album to datmusic album.
     *
     * @param  stdClass  $item
     * @param  stdClass|null  $artist
     * @return array
     *
     * @throws Exception
     */
    private function mapDeemixAlbum(stdClass $item, stdClass $artist = null)
    {
        if ($artist != null) {
            unset($artist->top);
            unset($artist->albums);
        }
        $mainArtist = $this->mapDeemixArtist($artist ?: $item->artist);
        $albumYear = property_exists($item, 'release_date') ? Carbon::parse($item->release_date)->year : 9999;
        $audios = property_exists($item, 'tracks') ? $this->cleanAudioList(app('request'), self::$SEARCH_BACKEND_AUDIOS, $this->mapDeemixSearchResults($item->tracks->data, self::$SEARCH_BACKEND_AUDIOS, null, $item), false) : [];
        $audiosCount = safeProp($item, 'nb_tracks', count($audios) ?: 10);

        $getAlbumCover = function ($type, $size) use ($item) {
            $cover = property_exists($item, $type) ? $item->{$type} : null;
            $coverMd5 = property_exists($item, 'md5_image') ? $item->md5_image : null;
            if ($cover == null && $coverMd5 != null) {
                return "https://e-cdns-images.dzcdn.net/images/cover/{$coverMd5}/{$size}x{$size}-000000-80-0-0.jpg";
            }

            return $cover;
        };

        return [
            'id'           => strval($item->id),
            'title'        => $item->title,
            'is_explicit'  => $item->explicit_lyrics,
            'type'         => $item->record_type,
            'genre_id'     => $item->genre_id,
            'count'        => $audiosCount,
            'year'         => $albumYear,
            'photo'        => [
                'photo_1200' => $getAlbumCover('cover_xl', 1000),
                'photo_600'  => $getAlbumCover('cover_big', 500),
                'photo_300'  => $getAlbumCover('cover_medium', 250),
            ],
            'main_artists' => [$mainArtist],
            'audios'       => $audios,
            'owner_id'     => $mainArtist['id'],
            'access_key'   => 'invalid',
        ];
    }
}
