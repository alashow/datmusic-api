<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\Request;

class Logger
{

    /**
     * Get formatted date
     * @return string current formatted date
     */
    private function getTime()
    {
        return date("F j, Y, g:i a");
    }

    /**
     * Writes log to file with given type and arguments array
     *
     * @param $type
     * @param array $args
     * @return int|bool bytes written count or false on failure
     */
    public function writeLog($type, array $args = ['null'])
    {
        $log = implode(' ', $args);
        $ip = Request::capture()->ip();
        $text = sprintf("%s, %s, %s, %s\n", $type, $this->getTime(), $log, $ip);

        return file_put_contents(config('app.paths.log'), $text, FILE_APPEND);
    }


    public function log($type, ...$args)
    {
        $this->writeLog($type, $args);
    }

    public function search(...$args)
    {
        return $this->writeLog("Search", $args);
    }

    public function searchCache(...$args)
    {
        return $this->writeLog("Search.Cache", $args);
    }

    public function download(...$args)
    {
        return $this->writeLog("Download", $args);
    }

    public function stream(...$args)
    {
        return $this->writeLog("Streaming", $args);
    }

    public function convert(...$args)
    {
        return $this->writeLog("Convert", $args);
    }

}