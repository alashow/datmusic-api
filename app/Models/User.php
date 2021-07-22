<?php
/*
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $incrementing = true;
    public $timestamps = true;
    protected $connection = 'postgres';
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $keyType = 'integer';

    protected $fillable = ['client_id'];

    protected $casts = [
        'id'         => 'integer',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public static function byClientId(string $clientId)
    {
        return self::firstOrCreate([
            'client_id' => $clientId,
        ]);
    }
}
