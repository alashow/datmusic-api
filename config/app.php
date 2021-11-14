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
        'hls' => [
            'enabled' => env('DOWNLOADING_HLS_ENABLED', false),
        ],
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
            'archive_cover' => env('DATMUSIC_DOWNLOAD_POST_PROCESS_ARCHIVE_COVER', false),
        ],
    ],

    'tools' => [
        'ffmpeg_path'    => 'ffmpeg',
    ],

    'covers'  => [
        'user-agent' => env('COVERS_USER_AGENT', sprintf('DatmusicApi/1.0.0 (+https://github.com/alashow/datmusic-api,%s)', fullUrl('/'))),
    ],

    'random_queries' => ['1000mods', '414Beg', 'A Whisper in the Noise', 'A Winged Victory for the Sullen', 'A$AP Rocky', 'Adebisi Shank', 'Aimee Mann', 'Akvavit', 'Alessandra Celletti', 'Alexis Ffrench',
        'Antennas to Heaven', "Aphrodite's Child", 'Apparat', 'April Shower', 'Aquaplasm', 'Árstíðir', 'Attono', 'Aukai', 'BLITZ', 'BLOND:ISH', 'Balthazar',  'Beady Eye',
        'Benj Heard', 'Beware of Safety', 'Biba Dupont', 'Björn Baummann', 'Black Mountain', 'Bonaparte', 'British Sea Power', 'Bror Gunnar Jansson', 'CLPPNG', 'Caitlyn Scarlett', 'Carlos Cipa',
        'Charlie Cunningham', 'Civil Twilight', 'Clam Pass', 'Colbie Caillat', 'Crippled Black Phoenix', 'Crown The Empire', 'DARKSIDE', 'Daniel Deluxe', 'Danny Brown', "David O'Dowda", 'Diamante Eléctrico', 'Edwyn Collins',
        'El-P', 'Ellis', 'Ever So Blue', 'Field Recordings', 'Floating Points', 'Flower Boy', 'Foals', 'For You', 'Fu Manchu', 'Future', 'Giles Corey',
        'Glowworm', 'Goonies Never Say Die', 'Graham Nash', 'Hidden Orchestra', 'Hideyuki Hashimoto', 'High Tropics', 'I Decided.', 'Indra', 'Ingrid Michaelson',
        'James Heather', 'Jason Mraz', 'Jeff Bright Jr', 'Jeff Russo', 'Joep Beving', 'Jon Hopkins', 'Jorge Méndez', 'Jude Christodal', 'Karin Borg', 'Kauai', 'Kiasmos',
        'Kronos Quartet Performs Philip Glass', 'LCD Soundsystem', 'La Femme', 'Lara Di Umbra', 'Lee Fields & The Expressions', 'Les Friction', 'Lévon Minassian',
        'Liam Singer', 'Long Distance Calling', 'Lorde', 'Los Makenzy', 'Loscil', 'Love Death Immortality', 'Love What Survives',
        "Lupe Fiasco's Food & Liquor", 'Lupe Sinsonte', 'M83', 'MIAOU', 'Magic Sword', 'Marika Hackman', 'Mario Batkovic', 'Medi Rela', 'Mega Drive',  'Michelle Gurevich',
        'Midgar', 'Migala', 'Migos', 'Milo Greene', 'Misha Mishenko', 'Mitch Murder', 'Monster Magnet', 'Moonlit Sailor', 'Mt. Wolf', 'NF', 'Nancy Sinatra', 'Nature Vibrations', 'Naxatras', 'Never Fold',
        'Niall Byrne', 'Pacific Rain', 'Pasquale Catalano', 'Philip Wesley', 'Phoebe Ryan', 'Placebo', 'Power Glove', 'Professional Rapper', 'Ranges', 'Ray LaMontagne',
        'Rejjie Snow', 'Relaxing Guru', 'Saltillo', 'Saxon Shore', 'Saycet', 'Scattle', 'Sea Power', 'Sigur Rós', 'Skyyy', 'Smino', 'Smokey Robinson & The Miracles',
        'Sólstafir', 'Soundtrack of Your Life', 'Stefano Guzzetti', 'Stephan Moccio', 'Stereophonics', 'Stormy Station', 'Tai Verdes', 'Tamer', 'Tamino', 'Texture Like Sun', 'The Chopin Project',
        'The Jungle Is The Only Way Out', 'The London Ensemble', 'The Nature Soundscapes', 'The Six Parts Seven', 'The Who', 'There Existed an Addiction to Blood', 'Tides From Nebula', 'Tom Caufield', 'Tracey Chattaway',
        'Truckfighters', 'Tunnel Blanket', 'Velvet Ears', 'Walking On Cars', 'Weather Pass', 'Weather and Nature Recordings', 'William Fitzsimmons', 'Wilsen', 'Wojciech Golczewski', 'Woodkid', 'Worlds to Run', 'Wrabel', 'Yasmine Hamdan', 'Yonderboi',
        'Yves Tumor', 'Zella Day', 'Zombie Hyperdrive', 'alt-J', 'deadmau5', 'envy', 'fingerspit', 'good kid', 'humidum', 'Andrew Kaiser', 'BROCKHAMPTON', 'Bastille', 'Benjamin Clementine', 'Brutus', 'Camo Columbo', 'Clark', 'Dario Marianelli',
        'Dark Lane Demo Tapes', 'Dark Sky Paradise', 'Donovan', 'DreamDrops', 'EARTHGANG', 'Edward Sharpe & The Magnetic Zeros', 'Evgeny Grinko', 'Explosions In The Sky', 'Florence + The Machine', 'Future Islands', 'God Is An Astronaut',
        'Goodbye Lenin !', 'If These Trees Could Talk', 'Intergalactic Lovers', 'J. Cole', 'Jarryd James', 'Jim James', 'José González', 'Josef Salvat', 'Kavinsky', 'Kevin Morby', 'Kid Cudi', 'Led Zeppelin', 'Lera Lynn',
        'Los Natas', 'Lost Years', 'Lucy Dacus', 'Luke Abbott', 'Macklemore & Ryan Lewis', 'Man Is Not a Bird', 'Martin Kohlstedt', 'Matti Bye', 'Milo', 'Mindful Measures', 'Monkey3', 'Nina Simone', "Patrick O'Hearn", 'Paul Simon', 'Rachel Grimes',
        'Raury', 'Reignwolf', 'Rival Consoles', 'SG Lewis', 'Snow Ghosts', 'Steaming Satellites', 'Takahiro Kido', 'The American Dollar', 'The Blue Stones', 'The Books', 'The Brothers Bright', 'The End Of The Ocean', 'The Gift of Affliction', 'The Veils',
        'The Weeknd', 'Thomas Bergersen', 'Thunder & Co.', 'Uncle Acid & The Deadbeats', 'Unfinished Business', 'Zombie Western',  '2CELLOS', '65daysofstatic', 'Agnes Obel', 'Archive', 'Carpenter Brut', 'Caspian', 'Christian Löffler', 'DaBaby', 'Dawn Golden',
        'Duval Timothy', 'Earl Sweatshirt', 'Eluvium', 'Flavien Berger', 'Funkadelic', 'IAMX', 'James Spiteri', 'Jefferson Airplane', 'Jesper Munk', 'Joy Wellboy', 'Kyle Landry', 'LBE Nature Recordings', 'Logic', 'Machete', 'Mammals', 'Maxence Cyrin', 'Moderat',
        'Moon Ate the Dark', 'Niklas Aman', 'Northerly Nature', 'Seafret', 'Secession Studios', 'Steve Miller Band', 'The Lumineers', 'The xx', 'Thomas Barrandon', 'Toygar Işıklı', 'Tvärvägen', 'VLNY', 'Waveshaper', 'Yndi Halda', 'Abel Korzeniowski', 'All Them Witches',
        'Brambles', 'Brant Bjork', 'Central do Brasil', 'Daft Punk', 'Daughter', 'Dead Meadow', 'Earthless', 'El Ten Eleven', 'Eliza Shaddad', 'Elsiane', 'Gem Club', 'Grails', 'Heinali', 'J2 the Iconic Series', 'Johnny Cash',
        'Kai Straw', 'Kanye West', 'Lambert', 'Lazerhawk', 'Nick Mulvey', 'Samsara Blues Experiment', 'Sophie Hutchings', 'The Doors', 'The Irrepressibles', 'The Rolling Stones', 'X Ambassadors', "We're Dreaming", 'm.A.A.d city', 'And the Things that Remain',
        'Andrew Bird', 'Angus & Julia Stone', 'City of the Sun', 'Coldplay', 'Drake', 'Ed Sheeran', 'For Now I Am Winter', 'Houses', 'Maybeshewill', 'Peter Broderick', 'Post Malone', 'Radio Moscow', 'Roberto Cacciapaglia', 'Rory Gallagher', 'Run The Jewels', 'Shlohmo',
        'Siena Root', 'The Album Leaf', 'The Healing Component', 'The Neighbourhood', 'This Will Destroy You', 'Travis Scott', 'Tyler', 'Broadchurch', 'Jalen Santoy', 'Kyuss', 'Max Korzh', 'Mick Jenkins', 'Nordic Giants', 'The Dixie Narcos',
        'The Fountain OST', 'Wye Oak', 'Yiruma', 'Lynyrd Skynyrd', 'Michele McLaughlin', 'Ramin Djawadi', 'The Evpatoria Report', 'Elements', 'Imagine Dragons', 'John Murphy', 'Lo-Fang', 'Mogwai', 'Snow Patrol', 'You+Me', 'pg.lost', 'Estas Tonne', 'Oh Hiroshima', 'Show Me A Dinosaur',
        'Andrew Belle', 'Arctic Monkeys', 'Collapse Under The Empire', 'Fink', 'Jaymes Young', 'Jóhann Jóhannsson', 'SOHN', 'Zoë Keating', 'toe', 'Antonio Pinto', 'Greg Haines', 'Greg Maroney', 'Keaton Henson', 'Röyksopp', 'Ben Howard', 'Kendrick Lamar', 'Romantic Works',
        'Balmorhea', 'Godspeed You! Black Emperor', 'Trance Frendz', 'Zes', 'Fabrizio Paterlini', 'The Glitch Mob', 'Yann Tiersen', 'BANKS', 'Mumford & Sons', 'Black Lab', "Rachel's", 'Of Monsters and Men', 'Childish Gambino', 'MONO', 'Nils Frahm', 'Clint Mansell', 'Kwoon', 'Message To Bears',
        'Zack Hemsey', 'Jimi Hendrix', 'Kai Engel', 'Dirk Maassen', 'clipping.', 'Hans Zimmer', 'Max Richter', 'Colour Haze', 'Ólafur Arnalds', 'Ludovico Einaudi', 'Mattia Cupelli', ],

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

    'client_logger' => [
        'require_headers' => env('DATMUSIC_CLIENT_HEADERS_REQUIRE', false),
    ],

    'services' => [
        'spotify' => [
            'enabled'       => env('SPOTIFY_CLIENT_ENABLED', false),
            'client_id'     => env('SPOTIFY_CLIENT_ID', ''),
            'client_secret' => env('SPOTIFY_CLIENT_SECRET', ''),
        ],
    ],

    'minerva' => [
        'fetch_covers' => env('MINERVA_FETCH_COVERS', false),

        'database'    => [
            'enabled' => env('MINERVA_DATABASE_ENABLED', false),
        ],
        'meilisearch' => [
            'enabled' => env('MINERVA_MEILISEARCH_ENABLED', false),
            'url' => env('MINERVA_MEILISEARCH_URL', ''),
            'index' => env('MINERVA_MEILISEARCH_INDEX', 'datmusic'),
            'key' => env('MINERVA_MEILISEARCH_KEY', ''),
        ],
    ],

    'deemix' => [
        'enabled'                  => env('DEEMIX_ENABLED', false),
        'api_url'                  => env('DEEMIX_API_URL', ''),
        'downloads_folder'         => env('DEEMIX_DOWNLOADS_FOLDER', 'Music'),
        'downloads_bitrate'        => env('DEEMIX_DOWNLOADS_BITRATE', 'flac'),
        'downloads_folder_rewrite' => env('DEEMIX_DOWNLOADS_FOLDER_REWRITE', 'flacs'), //rewrites download folder name in links
    ],
];
