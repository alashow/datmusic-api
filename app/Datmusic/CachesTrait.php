<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait CachesTrait
{
    /**
     * Get current request cache key.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getCacheKey($request)
    {
        $q = strtolower($request->get('q'));
        $page = abs(intval($request->get('page')));

        $q = empty($q) ? md5('popular') : $q;

        return hash(config('app.hash.cache'), ($q.$page));
    }

    /**
     * Save search result in cache.
     *
     * @param string $cacheKey cache key
     * @param array  $result   audio array
     */
    private function cacheSearchResult($cacheKey, $result)
    {
        Cache::put('query.'.$cacheKey, $result, config('app.cache.duration'));
    }

    /**
     * @param $request
     *
     * @return array|null audio array or null if not cached
     */
    private function getSearchResult($request)
    {
        return Cache::get('query.'.$this->getCacheKey($request));
    }

    /**
     * Get audio item from cache or abort with 404 if not found.
     *
     * @param string $key
     * @param string $id
     * @param $abort boolean aborts with 404 if not found, otherwise returns null
     *
     * @return mixed
     */
    public function getAudio($key, $id, $abort = true)
    {
        // get search cache instance
        $data = Cache::get('query.'.$key);

        if (is_null($data)) {
            logger()->log('Cache.NoAudio', $key, $id);
            if ($abort) {
                abort(404);
            }

            return null;
        }

        // search audio by audio id/hash
        $key = array_search($id, array_column($data, 'id'));

        if ($key === false) {
            if ($abort) {
                abort(404);
            }

            return null;
        }

        $item = $data[$key];
        $this->cacheAudioItem($id, $item);

        if (env('DATMUSIC_MP3_URL_DECODER', false)) {
            $item['mp3'] = decodeVkMp3Url($item['mp3']);
        }

        return $item;
    }

    /**
     * Save audio item in cache.
     *
     * @param $id string audio id
     * @param $item array audio item
     */
    public function cacheAudioItem($id, $item)
    {
        // we don't need to cache audios url, it's gonna expire anyways.
        unset($item['mp3']);

        return Cache::forever("audio.$id", $item);
    }

    /**
     * Get audio item from cache.
     *
     * @param $id string audio id
     */
    public function getAudioCache($id)
    {
        return Cache::get("audio.$id");
    }
}
