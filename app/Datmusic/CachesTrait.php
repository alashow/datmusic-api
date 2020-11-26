<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait CachesTrait
{
    private static $captchaLockPrefix = 'captchaLock';

    /**
     * Get current request cache key.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getCacheKey(Request $request)
    {
        $q = strtolower(getQuery($request));
        $page = getPage($request);

        $q = empty($q) ? md5('popular') : $q;

        return hash(config('app.hash.cache'), ($q.$page));
    }

    private function getCacheKeyForId(Request $request, string $id)
    {
        $page = getPage($request);

        return hash(config('app.hash.cache'), ($id.$page));
    }

    /**
     * Save search result in cache.
     *
     * @param string $cacheKey cache key
     * @param array  $result   audio array
     * @param string $type     cache type. from: blank for audio search type, albums, artists
     */
    private function cacheResult(string $cacheKey, array $result, string $type = '')
    {
        if (! blank($type)) {
            $type = "_$type";
        } // prefix with underscore if type is specified
        Cache::put("query$type.".$cacheKey, $result, config("app.cache.duration$type"));
    }

    /**
     * @param string $cacheKey
     * @param string $type cache type. from: blank for audio search type, albums, artists
     *
     * @return array|null audio array or null if not cached
     */
    private function getCache(string $cacheKey, string $type = '')
    {
        if (! blank($type)) {
            $type = "_$type";
        } // prefix with underscore if type is specified

        return Cache::get("query$type.".$cacheKey);
    }

    /**
     * Get audio item from cache or abort with 404 if not found.
     *
     * @param string $key
     * @param string $id
     * @param bool   $abort boolean aborts with 404 if not found, otherwise returns null
     *
     * @return array|null
     * @throws HttpException
     */
    public function getAudio(string $key, string $id, bool $abort = true)
    {
        $isAudio = $key == $this->audioKeyId;
        // get from audio cache or search cache
        $data = $isAudio ? $this->getAudioCache($id) : Cache::get('query.'.$key);

        if (is_null($data)) {
            logger()->log('Cache.NoAudio', $key, $id);
            if ($abort) {
                abort(404);
            }

            return null;
        }

        if ($isAudio) {
            return $data;
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

        return $item;
    }

    /**
     * Save audio item in cache.
     *
     * @param $id         string audio id
     * @param $item       array audio item
     * @param $expire     bool whether to save mp3 url
     *
     * @return bool
     */
    public function cacheAudioItem(string $id, array $item, bool $expire = false)
    {
        if (! $expire) {
            // we don't need to cache audios url, it's gonna expire anyways.
            unset($item['mp3']);

            return Cache::forever("audio.$id", $item);
        } else {
            return Cache::put("audio.$id", $item, config('app.cache.duration_audio'));
        }
    }

    /**
     * Get audio item from cache.
     *
     * @param $id string audio id
     *
     * @return array
     */
    public function getAudioCache($id)
    {
        return Cache::get("audio.$id");
    }

    public function captchaLock($accountIndex, $captcha)
    {
        $key = sprintf('%s#%s', self::$captchaLockPrefix, $accountIndex);

        return Cache::put($key, $captcha, config('app.captcha_lock.duration'));
    }

    public static function isCaptchaLocked($accountIndex)
    {
        $key = sprintf('%s#%s', self::$captchaLockPrefix, $accountIndex);

        return config('app.captcha_lock.enabled') && Cache::has($key);
    }

    public function getCaptchaLockError($accountIndex)
    {
        $key = sprintf('%s#%s', self::$captchaLockPrefix, $accountIndex);

        return Cache::get($key, false);
    }

    public function releaseCaptchaLock($accountIndex)
    {
        $key = sprintf('%s#%s', self::$captchaLockPrefix, $accountIndex);

        return Cache::forget($key);
    }

    private function captchaFailedAttemptCacheKey(Request $request)
    {
        return sprintf('%s.FailedAttempts#%s', self::$captchaLockPrefix, $request->getClientIp());
    }

    public function captchaFailedAttempt(Request $request)
    {
        $attemptsKey = $this->captchaFailedAttemptCacheKey($request);
        $attemptsCount = Cache::increment($attemptsKey);
        if ($attemptsCount > config('app.captcha_lock.allowed_failed_attempts')) {
            Cache::forget($attemptsKey);
            $this->banClientIp($request, config('app.captcha_lock.allowed_failed_attempts_duration'));
        }
    }

    public function getCaptchaFailedAttempts(Request $request)
    {
        return Cache::get($this->captchaFailedAttemptCacheKey($request), 0);
    }

    private function banClientCacheKey(Request $request)
    {
        return sprintf('clientBan@%s', $request->getClientIp());
    }

    private function banClientIp(Request $request, int $duration = 10 * 60, string $reason = '')
    {
        $key = $this->banClientCacheKey($request);
        $totalBans = Cache::increment(sprintf('%s.count', $key));
        logger()->banClient($totalBans, $duration, $reason);

        return Cache::put($key, $reason, $duration);
    }

    public function isClientBanned(Request $request)
    {
        return Cache::has($this->banClientCacheKey($request));
    }

    public function clientBanReason(Request $request)
    {
        return Cache::get($this->banClientCacheKey($request), false);
    }
}
