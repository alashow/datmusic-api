<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Util;

use App\Http\Middleware\ClientHeadersMiddleware;
use App\Http\Middleware\ResponseTimeMiddleware;
use Illuminate\Http\Request;

class Logger
{
    /**
     * Get formatted date.
     *
     * @return string current formatted date
     */
    private function getTime()
    {
        return date('F j, Y, g:i a');
    }

    /**
     * Writes log to file with given type and arguments array.
     *
     * @param  $type
     * @param  array  $args
     * @return int|bool bytes written count or false on failure
     */
    public function writeLog($type, array $args = ['null'])
    {
        $log = implode(' ', $args);
        $ip = Request::capture()->ip();
        $elapsed = ResponseTimeMiddleware::secondsSinceRequest();
        $clientInfo = ClientHeadersMiddleware::getClientInfoForLogger();
        $text = sprintf("%s, %s, %s, %s, %s, %s\n", $type, $this->getTime(), $log, $ip, $elapsed, $clientInfo);

        return file_put_contents(config('app.paths.log'), $text, FILE_APPEND);
    }

    public function log($type, ...$args)
    {
        $this->writeLog($type, $args);
    }

    public function search(...$args)
    {
        return $this->writeLog('Search', $args);
    }

    public function searchCache(...$args)
    {
        return $this->writeLog('Search.Cache', $args);
    }

    public function searchMultisearch(...$args)
    {
        return $this->writeLog('Search.Multisearch', $args);
    }

    public function searchMinervaMeilisearch(...$args)
    {
        return $this->writeLog('Search.MinervaMeilisearch', $args);
    }

    public function searchStatsHlsCount(...$args)
    {
        return $this->writeLog('Stats.HlsCount', $args);
    }

    public function statsHlsDownloadTime(...$args)
    {
        return $this->writeLog('Stats.HlsDownloadTime', $args);
    }

    public function searchBy($type, ...$args)
    {
        return $this->writeLog('Search.'.ucfirst($type), $args);
    }

    public function searchByCache($type, ...$args)
    {
        return $this->writeLog(sprintf('Search.%s.Cache', ucfirst($type)), $args);
    }

    public function getAlbumById(...$args)
    {
        return $this->writeLog('Get.AlbumById', $args);
    }

    public function getAlbumByIdCache(...$args)
    {
        return $this->writeLog('Get.AlbumById.Cache', $args);
    }

    public function getArtistItems($type, ...$args)
    {
        return $this->writeLog('Get.'.ucfirst($type), $args);
    }

    public function getArtistItemsCache($type, ...$args)
    {
        return $this->writeLog(sprintf('Get.%s.Cache', ucfirst($type)), $args);
    }

    public function download($cache, ...$args)
    {
        return $this->writeLog('Download'.($cache ? '.Cache' : ''), $args);
    }

    public function stream($cache, ...$args)
    {
        return $this->writeLog('Streaming'.($cache ? '.Cache' : ''), $args);
    }

    public function deemixDownload($stream, $cache, ...$args)
    {
        return $this->writeLog(($stream ? 'Streaming' : 'Download').'.Deemix'.($cache ? '.Cache' : ''), $args);
    }

    public function convert(...$args)
    {
        return $this->writeLog('Convert', $args);
    }

    public function captchaLock($index, ...$args)
    {
        return $this->writeLog("Captcha.Lock#$index", $args);
    }

    public function captchaLockedQuery($index, ...$args)
    {
        return $this->writeLog("Captcha.LockedQuery#$index", $args);
    }

    public function captchaSolved($index, ...$args)
    {
        return $this->writeLog("Captcha.Solved#$index", $args);
    }

    public function banClient(...$args)
    {
        return $this->writeLog('BanClient', $args);
    }

    public function banClientSkipped(...$args)
    {
        return $this->writeLog('BanClientSkipped', $args);
    }

    public function bannedClientRequest(...$args)
    {
        return $this->writeLog('BannedClientRequest', $args);
    }

    public function registerFcmToken(...$args)
    {
        return $this->writeLog('Users.RegisterFcm', $args);
    }
}
