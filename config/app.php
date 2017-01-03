<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

return [
    'cookiePath' => storage_path('app/cookies/%s.json'),
    'cachePath' => storage_path('app/cache'),
    'mp3Path' => storage_path('app/public/mp3'),

    // hashing algorithms
    'hash' => [
        'cache' => 'adler32',
        'id' => 'adler32',
        'mp3' => 'md5'
    ],

    'cache' => [
        'duration' => 24 * 60 // in minutes
    ],

    // account credentials, 0. phone number (without plus). 1. plain password
    // environment variable ACCOUNTS can override this
    'accounts' => [
        ['phone_number', 'password'],
    ],

    //allowing popular bitrates only: economy, standard, good, and best
    'allowed_bitrates' => [64, 128, 192],
    'allowed_bitrates_ffmpeg' => ["-q:a 9", "-q:a 5", "-q:a 2"],
    'ffmpeg_path' => 'ffmpeg'
];