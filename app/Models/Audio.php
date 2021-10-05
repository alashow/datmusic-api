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
    protected $connection = 'minerva';
    protected $table = 'audios';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'source_id', 'title', 'artist', 'duration', 'date', 'is_explicit',
        'cover_url', 'cover_url_medium', 'cover_url_small', 'created_at',
    ];

    protected $casts = [
        'id'          => 'string',
        'duration'    => 'integer',
        'date'        => 'timestamp',
        'is_explicit' => 'boolean',
        'created_at'  => 'timestamp',
    ];

    /**
     * Bulk insert given audio items.
     *
     * @param  array  $items
     */
    public static function insertAudioItems(array $items)
    {
        // sqlite will throw exception if bulk items have inconsistent field counts, so we're normalizing it here
        $items = array_map(function ($item) {
            // album and cover urls are only nullable fields for "normal" audios
            self::requireField($item, 'album');
            self::requireField($item, 'is_explicit', false);
            self::requireField($item, 'cover_url');
            self::requireField($item, 'cover_url_medium');
            self::requireField($item, 'cover_url_small');

            self::requireField($item, 'created_at', Date::now());

            unset($item['is_hls']);

            return $item;
        }, $items);

        self::upsert($items);
    }

    private static function upsert(array $items)
    {
        self::whereIn('id', collect($items)->pluck('id'))->delete();
        self::insert($items);
    }

    private static function requireField(&$item, $fieldName, $default = null): void
    {
        if (! array_key_exists($fieldName, $item)) {
            $item[$fieldName] = $default;
        }
    }
}
