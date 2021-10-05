<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use App\Models\Audio;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait DeemixTrait
{
    public static $DEEMIX_ID_PREFIX = 'dz.';

    private function verifyDeemixEnabled()
    {
        if (! config('app.deemix.enabled') || ! config('app.minerva.database.enabled')) {
            abort(500, 'Deemix or minerva not enabled');
        }
    }

    /**
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deemixSearch(Request $request)
    {
        $this->verifyDeemixEnabled();
        $backendName = self::$SEARCH_BACKEND_DEEMIX;

        $query = getQuery($request);
        $pageBy = 50;
        $offset = getPage($request) * $pageBy;

        if (empty($query)) {
            $query = randomQuery();
        }

        $cacheKey = $this->getCacheKey($request);
        $cachedResult = $this->getCache($cacheKey, $backendName);
        $isCachedQuery = ! is_null($cachedResult);

        if ($isCachedQuery) {
            logger()->searchByCache($backendName, $query, $offset, 'count='.count($cachedResult));

            return okResponse($this->cleanAudioList($request, $backendName, $cachedResult), $backendName);
        }

        $response = json_decode(deemixClient()->get('search', [
            'query' => [
                'q'      => $query,
                'offset' => $offset,
                'limit'  => $pageBy,
            ],
        ])->getBody());

        $tracks = $this->mapDeemixSearchResults($response->data);
        $hitsCount = $response->total;

        $this->cacheResult($cacheKey, $tracks, $backendName);

        logger()->searchBy($backendName, $query, $offset, 'count='.$hitsCount);

        return okResponse($this->cleanAudioList($request, $backendName, $tracks, false), $backendName);
    }

    /**
     * @param  Request  $request
     * @param  string  $id
     * @param  bool  $stream
     * @return RedirectResponse
     */
    public function deemixDownload(Request $request, string $id, bool $stream = false)
    {
        $this->verifyDeemixEnabled();

        $cached = Audio::find($id);
        if ($cached != null) {
            return $this->deemixDownloadResponse($stream, true, $id, $cached->source_id);
        }

        $trackId = str_replace(self::$DEEMIX_ID_PREFIX, '', $id);
        $bitrate = config('app.deemix.downloads_bitrate');
        $dlPath = sprintf('dl/track/%s/%s', $trackId, $bitrate);

        $response = json_decode(deemixClient()->get($dlPath)->getBody());
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

    public function isDeemixId($id)
    {
        return Str::startsWith($id, self::$DEEMIX_ID_PREFIX);
    }

    private function mapDeemixSearchResults($data)
    {
        return array_map(function ($item) {
            return [
                'id'               => self::$DEEMIX_ID_PREFIX.$item->id,
                'source_id'        => strval($item->id),
                'title'            => $item->title,
                'artist'           => $item->artist->name,
                'album'            => $item->album->title,
                'cover_url'        => $item->album->cover_xl,
                'cover_url_medium' => $item->album->cover_big,
                'cover_url_small'  => $item->album->cover_medium,
                'duration'         => $item->duration,
                'is_explicit'      => $item->explicit_lyrics,
            ];
        }, $data);
    }

    private function mapDeemixDownloadResult($data)
    {
        $trackExtendedInfo = $data->single->trackAPI_gw;

        return [
            'id'               => self::$DEEMIX_ID_PREFIX.$data->id,
            'source_id'        => $data->files[0],
            'title'            => $data->title,
            'artist'           => $data->artist,
            'album'            => $trackExtendedInfo->ALB_TITLE,
            'cover_url'        => $data->cover,
            'cover_url_medium' => $data->cover_big,
            'cover_url_small'  => $data->cover_medium,
            'duration'         => intval($trackExtendedInfo->DURATION),
            'is_explicit'      => $data->explicit,
            'date'             => Carbon::parse($trackExtendedInfo->PHYSICAL_RELEASE_DATE)->timestamp,
            'extra_info'       => json_encode($trackExtendedInfo),
        ];
    }
}
