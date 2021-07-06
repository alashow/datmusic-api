<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

return [

    'paths' => [
        'cookie' => storage_path('app/cookies/%s.json'),
        'mp3'    => env('DATMUSIC_PATHS_MP3', storage_path('app/public/mp3')),
        'links'  => env('DATMUSIC_PATHS_MP3_LINKS', storage_path('app/public/links')),
        'log'    => env('DATMUSIC_PATHS_LOG_FILE', storage_path('logs/datmusic.log')),
    ],

    // hashing algorithms
    'hash'  => [
        'cache' => 'crc32',
        'id'    => 'crc32',
        'mp3'   => 'md5',
        'cover' => 'md5',
    ],

    'search' => [
        // moves song to end if matches
        'sortRegex' => '/[ \[\],.:\)\(\-_](bass ?boost(ed)?|dub sound|remake|low bass|cover|(re)?mix|dj|bootleg|edit|aco?ustic|instrumental|karaoke|tribute|vs|rework|mash|rmx|(night|day|slow)core|remode|ringtone?|рингтон|РИНГТОН|Рингтон|звонок|минус)([ ,.:\[\]\)\(\-_].*)?$/i',

        // replaces with empty string if matches
        'badWordsRegex' => '/(https?:\/\/)?(vkontakte|vk)\.?(com|ru)?\/?(club|id)?/i',
    ],

    'cache' => [
        // in seconds
        'duration' => 24 * 60 * 60,
        'duration_audio' => 24 * 60 * 60,
        'duration_artists' => 24 * 60 * 60 * 7,
        'duration_albums' => 24 * 60 * 60 * 7,
    ],

    'auth' => [
        // environment variable AUTH_ACCESS_TOKENS can override this
        'tokens' => env('AUTH_ACCESS_TOKENS'),
    ],

    'downloading' => [
        'timeout' => [
            //seconds
            'connection' => 2, // connection timeout
            'execution'  => 60, // downloading timeout
        ],

        'id3' => [
            'enable'  => env('DOWNLOADING_ID3_TAGS_ENABLED', true),
            'comment' => 'Downloaded via https://datmusic.xyz',

            'download_covers'          => env('DOWNLOADING_ID3_COVERS', true),
            'download_covers_external' => env('DOWNLOADING_ID3_COVERS_EXTERNAL', false),
        ],

        'post_process' => [
            'enabled'  => env('DATMUSIC_DOWNLOAD_POST_PROCESS_ENABLED', false),
            'sink_url' => env('DATMUSIC_DOWNLOAD_POST_PROCESS_SINK_URL', null),
            'archive_cover' => env('DATMUSIC_DOWNLOAD_POST_PROCESS_ARCHIVE_COVER', false),
        ],
    ],

    'conversion' => [
        //popular bitrates: economy, standard, good
        'allowed'        => [64, 128, 192],
        'allowed_ffmpeg' => ['-q:a 9', '-q:a 5', '-q:a 2'],
        'ffmpeg_path'    => 'ffmpeg',
    ],

    'covers'  => [
        'user-agent' => env('COVERS_USER_AGENT', sprintf('DatmusicApi/1.0.0 (+https://github.com/alashow/datmusic-api,%s)', fullUrl('/'))),
    ],

    // random artist search
    'artists' => [
        '2 Cellos', 'Agnes Obel', 'Aloe Black', 'Andrew Belle', 'Angus Stone', 'Aquilo', 'Arctic Monkeys',
        'Avicii', 'Balmorhea', 'Barcelona', 'Bastille', 'Ben Howard', 'Benj Heard', 'Birdy', 'Broods',
        'Calvin Harris', 'Charlotte OC', 'City of The Sun', 'Civil Twilight', 'Clint Mansel', 'Coldplay',
        'Daft Punk', 'Damien Rice', 'Daniela Andrade', 'Daughter', "David O'Dowda", 'Dawn Golden', 'Dirk Maassen',
        'Ed Sheeran', 'Eminem', 'Fabrizio Paterlini', 'Fink', 'Fleurie', 'Florence and The Machine', 'Gem club',
        'Glass Animals', 'Greg Haines', 'Greg Maroney', 'Halsey', 'Hans Zimmer', 'Hozier',
        'Imagine Dragons', 'Ingrid Michaelson', 'Jamie XX', 'Jarryd James', 'Jasmin Thompson', 'Jaymes Young',
        'Jessie J', 'Josef Salvat', 'Julia Kent', 'Kai Engel', 'Keaton Henson', 'Kendra Logozar', 'Kina Grannis',
        'Kodaline', 'Kygo', 'Kyle Landry', 'Lana Del Rey', 'Lera Lynn', 'Lights & Motion', 'Linus Young', 'Lo-Fang',
        'Lorde', 'Ludovico Einaudi', 'M83', 'MONO', 'MS MR', 'Macklemore', 'Mammals', 'Maroon 5', 'Martin Garrix',
        'Mattia Cupelli', 'Max Richter', 'Message To Bears', 'Mogwai', 'Mumford & Sons', 'Nils Frahm', 'ODESZA', 'Oasis',
        'Of Monsters and Men', 'Oh Wonder', 'Philip Glass', 'Phoebe Ryan', 'Rachel Grimes', 'Radiohead', 'Ryan Keen',
        'Sam Smith', 'Seinabo Sey', 'Sia', 'Takahiro Kido', 'The Irrepressibles', 'The Neighbourhood', 'The xx',
        'VLNY', 'Wye Oak', 'X ambassadors', 'Yann Tiersen', 'Yiruma', 'Young Summer', 'Zack Hemsey', 'Zinovia',
        'deadmau5', 'pg.lost', 'Ólafur Arnalds',
    ],

    'captcha_lock' => [
        // locks accounts on captcha error until or timeout with duration
        'enabled' => env('DATMUSIC_CAPTCHA_LOCK_ENABLED', true),

        // depending on your needs, locked or unlocked tokens can be preferred i.e more weight = more chances of being picked as a search token
        'weighted_tokens_enabled' => env('DATMUSIC_CAPTCHA_LOCK_WEIGHTED_TOKENS_ENABLED', true),
        'unlocked_token_weight'   => env('DATMUSIC_CAPTCHA_LOCK_UNLOCKED_TOKEN_WEIGHT', 4),
        'locked_token_weight'     => env('DATMUSIC_CAPTCHA_LOCK_LOCKED_TOKEN_WEIGHT', 1),

        //seconds
        'duration' => env('DATMUSIC_CAPTCHA_LOCK_DURATION', 5 * 60), //how to long to lock account when captcha received

        // failure is detected when there's a captcha error in search response and captcha key in request
        'allowed_failed_attempts' => 15, // how many times the client is allowed to send wrong captcha keys
        'allowed_failed_attempts_duration' => 2 * 60, // before they get banned for n seconds

    ],

    'client_bans' => [
        'ip_whitelist' => explode(',', env('DATMUSIC_CLIENT_BANS_IP_WHITELIST', '')),
    ],

    'services' => [
        'spotify' => [
            'enabled'       => env('SPOTIFY_CLIENT_ENABLED', false),
            'client_id'     => env('SPOTIFY_CLIENT_ID', ''),
            'client_secret' => env('SPOTIFY_CLIENT_SECRET', ''),
        ],
    ],
];
