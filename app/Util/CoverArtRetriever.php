<?php
/*
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Util;

trait CoverArtRetriever
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
        if (! in_array($size, CoverArtRetriever::$SIZES)) {
            abort(400, 'Unknown size');
        }

        return $size;
    }

    abstract public function findCover(string $artist, string $title, string $size);

    public function findArtistCover(string $artist, string $size)
    {
        return false;
    }
}
