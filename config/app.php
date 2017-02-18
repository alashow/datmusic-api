<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

use Aws\Credentials\CredentialProvider;

return [

    'paths' => [
        'cookie' => storage_path('app/cookies/%s.json'),
        'mp3' => storage_path('app/public/mp3'),
    ],

    // hashing algorithms
    'hash' => [
        'cache' => 'crc32',
        'id' => 'crc32',
        'mp3' => 'md5'
    ],

    'search' => [
        // how many pages need to get for each page.
        // ex: if value is 2, two pages will be for each requested.
        // flush the cache after changing this value
        'pageMultiplier' => 2,

        // moves song to end if matches
        'sortRegex' => '/[ \[\],.:\)\(\-_](bass ?boost(ed)?|dub sound|remake|low bass|cover|(re)?mix|dj|bootleg|edit|aco?ustic|instrumental|karaoke|tribute|vs|rework|mash|rmx|(night|day|slow)core|remode|ringtone?|рингтон|РИНГТОН|Рингтон|звонок|минус)([ ,.:\[\]\)\(\-_].*)?$/i',

        // replaces with empty string if matches
        'badWordsRegex' => '/(https?:\/\/)?(vkontakte|vk)\.?(com|ru)?\/?(club|id)?/i'
    ],

    'cache' => [
        'duration' => 24 * 60 // in minutes
    ],

    // account credentials, 0. phone number (without plus). 1. plain password
    // environment variable ACCOUNTS can override this
    'accounts' => [
        ['phone_number', 'password'],
    ],

    'downloading' => [
        'timeout' => [
            //seconds
            'connection' => 2, // connection timeout
            'execution' => 60, // downloading timeout
        ]
    ],

    'conversion' => [
        //popular bitrates: economy, standard, good
        'allowed' => [64, 128, 192],
        'allowed_ffmpeg' => ["-q:a 9", "-q:a 5", "-q:a 2"],
        'ffmpeg_path' => 'ffmpeg'
    ],

    'aws' => [
        'enabled' => false,

        'config' => [
            'version' => 'latest',
            'region' => 'eu-central-1',

            //http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html#env-provider
            'credentials' => CredentialProvider::env()
        ],

        'bucket' => 'datmusic',

        'paths' => [
            // will be formatted with mp3 file name
            'mp3' => 'mp3/%s'
        ]
    ],

    // vk has bug, they don't have mp3 urls in audio items. even their site doesn't work
    // you might want to disable it until they fix it
    'popularSearchEnabled' => false,

    // random artist search
    'artists' => [
        "2 Cellos", "Agnes Obel", "Aloe Black", "Andrew Belle", "Angus Stone", "Aquilo", "Arctic Monkeys",
        "Avicii", "Balmorhea", "Barcelona", "Bastille", "Ben Howard", "Benj Heard", "Birdy", "Broods",
        "Calvin Harris", "Charlotte OC", "City of The Sun", "Civil Twilight", "Clint Mansel", "Coldplay",
        "Daft Punk", "Damien Rice", "Daniela Andrade", "Daughter", "David O'Dowda", "Dawn Golden", "Dirk Maassen",
        "Ed Sheeran", "Eminem", "Fabrizio Paterlini", "Fink", "Fleurie", "Florence and The Machine", "Gem club",
        "Glass Animals", "Greg Haines", "Greg Maroney", "Groen Land", "Halsey", "Hans Zimmer", "Hozier",
        "Imagine Dragons", "Ingrid Michaelson", "Jamie XX", "Jarryd James", "Jasmin Thompson", "Jaymes Young",
        "Jessie J", "Josef Salvat", "Julia Kent", "Kai Engel", "Keaton Henson", "Kendra Logozar", "Kina Grannis",
        "Kodaline", "Kygo", "Kyle Landry", "Lana Del Rey", "Lera Lynn", "Lights & Motion", "Linus Young", "Lo-Fang",
        "Lorde", "Ludovico Einaudi", "M83", "MONO", "MS MR", "Macklemore", "Mammals", "Maroon 5", "Martin Garrix",
        "Mattia Cupelli", "Max Richter", "Message To Bears", "Mogwai", "Mumford & Sons", "Nils Frahm", "ODESZA", "Oasis",
        "Of Monsters and Men", "Oh Wonder", "Philip Glass", "Phoebe Ryan", "Rachel Grimes", "Radiohead", "Ryan Keen",
        "Sam Smith", "Seinabo Sey", "Sia", "Takahiro Kido", "The Irrepressibles", "The Neighbourhood", "The xx",
        "VLNY", "Wye Oak", "X ambassadors", "Yann Tiersen", "Yiruma", "Young Summer", "Zack Hemsey", "Zinovia",
        "deadmau5", "pg.lost", "Ólafur Arnalds"
    ],
];