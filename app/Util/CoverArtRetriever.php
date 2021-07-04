<?php
/*
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Util;

trait CoverArtRetriever
{
    static $SIZES = ['large', 'medium', 'small'];
    static $SIZE_LARGE = 'large';
    static $SIZE_MEDIUM = 'medium';
    static $SIZE_SMALL = 'small';

    static function validateSize($size, $default = 'small')
    {
        if (! $size) $size = $default;
        if (! in_array($size, CoverArtRetriever::$SIZES)) {
            abort(400, 'Unknown size');
        }
        return $size;
    }

    public abstract function findCover(string $artist, string $title, string $size);

    public function findArtistCover(string $artist, string $size)
    {
        return false;
    }
}