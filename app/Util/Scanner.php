<?php
/*
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Util;

trait Scanner
{
    public static $SIZES = ['large', 'medium', 'small'];
    public static $SIZE_LARGE = 'large';
    public static $SIZE_MEDIUM = 'medium';
    public static $SIZE_SMALL = 'small';

    public static function validateSize($size, $default = 'medium')
    {
        if (! $size) {
            $size = $default;
        }
        if (! in_array($size, Scanner::$SIZES)) {
            abort(400, 'Unknown size');
        }

        return $size;
    }

    /**
     * @param  string  $artist  artist name
     * @param  string  $title  song name
     * @return false|array cover image array or false if fails
     */
    abstract public function findCover(string $artist, string $title);

    /**
     * @param  string  $artist
     * @return false|array artist details or false if fails
     */
    public function findArtist(string $artist)
    {
        return false;
    }

    /**
     * @param  string  $artist  artist name
     * @return false|array artist image array or false if fails
     */
    public function findArtistImage(string $artist)
    {
        return false;
    }
}
