<?php
/*
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class Audio extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'audios';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    protected $fillable = ['id', 'source_id', 'title', 'artist', 'duration', 'date', 'cover_url', 'cover_url_medium', 'cover_url_small'];

    /**
     * Bulk insert given audio items.
     *
     * @param array $audioItems
     */
    public static function insertAudioItems(array $audioItems)
    {
        // sqlite will throw exception if bulk items have inconsistent field counts
        // album and cover urls are only nullable fields for "normal" audios
        $items = array_map(function ($item) {
            self::requireField($item, 'album');
            self::requireField($item, 'cover_url');
            self::requireField($item, 'cover_url_medium');
            self::requireField($item, 'cover_url_small');

            self::requireField($item, 'created_at', Date::now());

            return $item;
        }, $audioItems);

        Audio::insert($items);
    }

    private static function requireField(&$item, $fieldName, $default = null): void
    {
        if (! array_key_exists($fieldName, $item)) {
            $item[$fieldName] = $default;
        }
    }
}
