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

    'random_queries' => [
        "Ludovico Einaudi", "Ólafur Arnalds", "Mattia Cupelli", "Max Richter", "Hans Zimmer", "Zack Hemsey", "Message To Bears", "The Glitch Mob",
        "Nils Frahm", "Dirk Maassen", "Kai Engel", "Of Monsters and Men", "Clint Mansell", "Kwoon", "Colour Haze", "Balmorhea", "SOHN", "Kendrick Lamar",
        "Mick Jenkins", "MONO", "Keaton Henson", "Estas Tonne", "Childish Gambino", "BANKS", "Antonio Pinto", "toe", "clipping.", "Zes", "Yann Tiersen",
        "Röyksopp", "Mumford & Sons", "Drake", "Rachel's", "Lo-Fang", "Fink", "Wye Oak", "M83", "John Murphy", "Greg Haines", "Fabrizio Paterlini",
        "Ed Sheeran", "Arnór Dan", "Yiruma", "Vince Staples", "Tyler, The Creator", "Ryan Lewis", "Run The Jewels", "Philip Glass", "Peter Broderick",
        "Oh Hiroshima", "Macklemore & Ryan Lewis", "Macklemore", "Kygo", "Jaymes Young", "Imagine Dragons", "Andrew Belle", "Zoë Keating",
        "The Evpatoria Report", "Shlohmo", "Ren Ford", "Pusha T", "Nordic Giants", "Kronos Quartet", "Houses", "Greg Maroney", "Gem Club", "Eminem",
        "Daniel Hope", "City of the Sun", "You+Me", "X Ambassadors", "VLNY", "This Will Destroy You", "The xx", "The Neighbourhood", "Nina Simone", "NF",
        "Mogwai", "Kid Cudi", "Kanye West", "Jóhann Jóhannsson", "J2", "IAMX", "Daughter", "Coldplay", "Black Lab", "Ben Howard", "Arctic Monkeys",
        "All Them Witches", "Alice Sara Ott", "deadmau5", "Will Cady", "WELL$", "Travis Scott", "The Weeknd", "The Dixie Narcos", "ScHoolboy Q",
        "Roberto Cacciapaglia", "Rival Consoles", "Ramin Djawadi", "Michele McLaughlin", "Max Korzh", "Konzerthaus Kammerorchester Berlin", "Kai Straw",
        "Joey Bada$$", "Jimi Hendrix", "Jim James", "JPEGMAFIA", "J. Cole", "Injury Reserve", "Heinali", "Ezio Bosso", "Erick the Architect",
        "Earl Sweatshirt", "Daft Punk", "Collapse Under The Empire", "Christian Löffler", "Big Sean", "Bastille", "BROCKHAMPTON", "Andre de Ridder",
        "Anderson .Paak", "Amsterdam Sinfonietta", "Abel Korzeniowski", "A\$AP Rocky", "alt-J", "Zinovia", "Walking On Cars", "Vitamin String Quartet",
        "The Stooges", "The Irrepressibles", "Steaming Satellites", "Sophie Hutchings", "Snow Ghosts", "Show Me A Dinosaur", "Seinabo Sey", "Sea Power",
        "Nick Mulvey", "Monster Magnet", "Mereba", "Martin Garrix", "Mammals", "Luke Howard", "Lorde", "Lil Wayne", "Lera Lynn",
        "Lee Fields & The Expressions", "Lambert", "Kyuss", "Kyle Landry", "Josef Salvat", "Jon Hopkins", "Joep Beving", "Jalen Santoy",
        "Jacques Morelenbaum", "If These Trees Could Talk", "Grails", "Glowworm", "Future", "EARTHGANG", "Deutsches Filmorchester Babelsberg",
        "David O'Dowda", "Dave East", "Danny Brown", "DaBaby", "Chris Brown", "Charles Bradley", "Caitlyn Scarlett", "Benjamin Clementine",
        "Antonio Vivaldi", "Angus & Julia Stone", "Andrew Bird", "Ameer Vann", "ABBY", "pg.lost", "fun.", "cktrl", "Young Fathers", "Yasmine Hamdan",
        "Woodkid", "Winter Aid", "Wilsen", "Tvärvägen", "Traffic", "Toygar Işıklı", "Tom Waits", "Tom Caufield", "Tigran Hamasyan", "Three Days Grace",
        "Thomas Bergersen", "The Veils", "The National", "The Lyndhurst Orchestra", "The Lumineers", "The Devil and the Almighty Blues",
        "The Cinematic Orchestra", "The Brian Jonestown Massacre", "The Books", "The Album Leaf", "Takahiro Kido", "Sylvan Paul", "Stacey Watton",
        "Spindrift", "Snow Patrol", "Snoop Dogg", "Slowdive", "Siena Root", "Sia", "Sergio Díaz De Rojas", "Secession Studios", "Sébastien Tellier",
        "Seafret", "Screamin' Jay Hawkins", "Scattle", "Saycet", "Samsara Blues Experiment", "Saltillo", "Saba", "STACEY", "SG Lewis", "Ryan Keen",
        "Rupert Gregson-Williams", "Ruelle", "Rodríguez", "Redi Hasa", "Raphael Alpermann", "Raekwon", "Rachel Grimes", "RY X", "Patrick O'Hearn",
        "Otis Redding", "Oddisee", "Nicolas Jaar", "Nancy Sinatra", "Moon Ate the Dark", "Monkey3", "Moderat", "Misha Mishenko", "Milo", "Michelle Gurevich",
        "Mensch", "Menahan Street Band", "Maybeshewill", "Maxence Cyrin", "Max Knoth", "Martin Kohlstedt", "Mario Batkovic", "Man Is Not a Bird",
        "Made in Heights", "Maarten Jansen", "Lynyrd Skynyrd", "Lupe Fiasco", "Luke Abbott", "Ludwig van Beethoven", "Lucy Dacus", "Love Supreme",
        "Los Natas", "Liz Kenny", "Little Walter", "Linus Young", "Lee Fields", "Led Zeppelin", "La Pietà", "LCD Soundsystem", "Kiasmos", "Kevin Morby",
        "Kavinsky", "Kai Schumacher", "Julian Rachlin", "Julia Kent", "Joy Wellboy", "José González", "Johnny Thunder", "John Frusciante", "Jill Scott",
        "Jeroen van Veen", "Janine Jansen", "Janelle Monáe", "Jan Jansen", "Jamie xx", "Jamie N Commons", "James Spiteri", "JAY-Z", "Hozier",
        "Hildur Guðnadóttir", "Hidden Orchestra", "Henk Rubingh", "Helen Jane Long", "Godspeed You! Black Emperor", "Gesaffelstein", "Gavin Greenaway",
        "Funkadelic", "Foals", "Florence + The Machine", "Fleurie", "Flavien Berger", "Federico Mecozzi", "Erik Satie", "Elsiane", "Eliza Shaddad", "El-P",
        "Earthless", "Dimitri Artemenko", "DeadMono", "Dario Marianelli", "Daniel Lanois", "DARKSIDE", "Clark", "Christopher O'Riley", "Christophe Beck",
        "Chequerboard", "CeeLo Green", "Candida Thompson", "Bruce Brubaker", "Black Diamond Heavies", "Birdy", "Bang On A Can All-Stars", "Aukai",
        "Árstíðir", "Archive", "Anonymuz", "Angèle Dubeau", "Andrew Kaiser", "Andrew Hewitt", "Amongster", "Alexis Ffrench", "Alex Wiley", "Alabama Shakes",
        "Aja Volkman", "Ah! Kosmos", "Agnes Obel", "2CELLOS", "13 & God", "theMIND", "the Gospel Queens", "sleepmakeswaves", "múm", "isaac gracie", "haan",
        "grandson", "fingerspit", "ef", "cln", "billy woods", "amiina", "Zyra", "Zwette", "Zombie Western", "Zombie Juice", "Zola Jesus", "Zola Dubnikova",
        "Zelooperz", "Yuki Murata", "Yppah", "Young the Giant", "Yonderboi", "Yom", "Yoko Shimomura", "Yndi Halda", "Yatao", "YOB", "YGTUT", "YARMAK",
        "Xiu Xiu", "Wrabel", "Wovenhand", "Working for a Nuclear Free City", "Wolf Colony", "Wolf Alice", "Wojciech Golczewski", "Witch", "Winston Marshall",
        "Wim Mertens", "William Fitzsimmons", "Will Heard", "Wildhood", "Wild Beasts", "Wilco", "Wiener Philharmoniker", "Weval", "Wet", "We Lost The Sea",
        "We Are All Astronauts", "Warren Ellis", "Wankelmut", "Waldo", "WYS", "WDL", "Vitalic", "Vincenzo Bellini", "Vidia Wesenlund", "Vetusta Morla",
        "Velvet Ears", "Vaux", "Valentin Stip", "Uncle Acid & The Deadbeats", "Ulster Orchestra", "UNKLE", "Tyler Bates", "Tyga", "Tycho",
        "Two Steps from Hell", "Turya", "Turtle", "Turner Cody", "Trummor & Orgel", "True Widow", "Truckfighters", "Trifonic", "Trey Chui-yee Lee",
        "Trevor Yuile", "Tracey Chattaway", "Townes Van Zandt", "Tourist", "Torgny", "Top Drawer", "Tony Allen", "Tonus Peregrinus", "Tonie Green",
        "Tomsize", "Tom Odell", "Tom Kerstens", "Tom Hodge", "Tol-Puddle Martyrs", "Token", "Timo Vollbrecht", "Timber Timbre", "Tim Rice",
        "Tides From Nebula", "Thundercat", "Thunder & Co.", "Those Who Ride With Giants", "Thomas Newman", "Thomas Barrandon", "Thom Yorke",
        "This Patch of Sky", "Theophilus London", "Theo Hakola", "The Who", "The Trinidad Singers", "The Temper Trap", "The Spell", "The Sherry Sisters",
        "The Rolling Stones", "The Rita", "The Revivalists", "The Pilgrim", "The Piano Guys", "The Orb", "The Ocean", "The Mind", "The London Ensemble",
        "The Last Dinosaur", "The Knocks", "The Kilimanjaro Darkjazz Ensemble", "The Kickdrums", "The Jezabels", "The Ink Spots", "The Horrors",
        "The Hope Arsenal", "The Hoosiers", "The Heliocentrics", "The Half Earth", "The Gloaming", "The Game", "The Flashbulb", "The Flaming Lips",
        "The Feelies", "The Eye Of Time", "The Equatics", "The End Of The Ocean", "The Dwarf Cast", "The Doors", "The Districts", "The Coup",
        "The Comet Is Coming", "The Chamber Orchestra Of London", "The Budos Band", "The Boxer Rebellion", "The Blue Stones", "The Black Keys",
        "The Beta Band", "The Barr Brothers", "The Avener", "The American Dollar", "The Affirmations", "The Acid", "Tha Dogg Pound", "Teyana Taylor",
        "Texture Like Sun", "Terje Isungset", "Teho Teardo", "Tedashii", "Tatiana Grindenko", "Tankz", "Tangerine Stoned", "Tamino", "Tamer", "Tambour",
        "Talos", "Takuo Yuasa", "Takénobu", "Ta-ku", "TV On The Radio", "TORRES", "TOKiMONSTA", "TAYA", "T.I.", "Sysyphe", "Syn Cole", "Sylversky",
        "Sylvan LaCue", "Sylvan Esso", "Swan", "Susanne Sundfør", "Superpoze", "Subculture Sage", "Stu Larsen", "Still Corners", "Stevie Nicks",
        "Steven Segal", "Steven Price", "Steve Jablonsky", "Stereophonics", "Steppenwolf", "Stephen Smith", "Stefano Guzzetti", "Stealers Wheel", "Starkey",
        "Starcadian", "Stanton Lanier", "Stan Hubbs", "Staind", "Spoiwo", "Spiro", "Spillage Village", "Spidergawd", "Spaceman Spiff",
        "Soundtrack of Your Life", "Soundmouse", "Songs: Ohia", "Sólstafir", "Solomon Grey", "Sóley", "Sofi Tukker", "Slow Skies", "Slow Dancing Society",
        "Slim Harpo", "Sleepy Minds", "Sleeping At Last", "Skysketch", "Skunk Anansie", "Skraeckoedlan", "Skepta", "Sk La Flare", "Sjava", "Sizzy Rocket",
        "Sir Georg Solti", "Sipho the Gift", "Sinoia Caves", "Sin Fang", "Simos Papanas", "Simeon", "Silver Mt. Zion", "Sidewalks and Skeletons",
        "Shy Glizzy", "Shuggie Otis", "Shigeru Umebayashi", "Shearwater", "Shawn James", "Serge Gainsbourg", "Seckou Keita", "Sebastian and the Deep Blue",
        "Sebastian Szary", "Sebastian Plano", "Sebastian Kamae", "Sea Wolf", "Savoy Brown", "Saulus Sondeckis", "Sascha Ring", "Sarah Jaffe", "Sarah Bernat",
        "Sandra van Veen", "Samaris", "Sam Sure", "Sam Airey", "Sacri Monti", "Sabicas", "SYML", "SBTRKT", "SATV Music", "SALES", "SALEM",
        "Ryuichi Sakamoto", "Ryn Weaver", "Rural Zombies", "Royce Da 5'9", "Royal Blood", "Roya", "Roy Buchanan", "Roxy Music", "Rose's Pawn Shop", "Roscoe",
        "Rosco Gordon", "Rory Gallagher", "Romy", "Roger Goula", "Rod Stewart", "Robyn", "Robin Schulz", "Roberto Attanasio", "River Whyless", "River Tiber",
        "Riri Shimada", "Rihanna", "Richard Wagner", "Richard Kapp", "Richard Dillon", "Richard Armitage", "Rich Boy", "Remo Giazotto", "Rejjie Snow",
        "Reignwolf", "Red Snapper", "Red", "Rayland Baxter", "Ray Dalton", "Raury", "Rauelsson", "Ranges", "Random Forest", "Ralph Carney",
        "Rainbow Kitten Surprise", "Rag'n'Bone Man", "Rafferty", "Raffertie", "Radu Lupu", "Radio Moscow", "Rachel Currea", "RJD2", "RITUAL", "RIOPY",
        "REASON", "Queens of the Stone Age", "Pyrit", "Puscifer", "Pumarosa", "Public Service Broadcasting", "Prequell", "Pray for Sound", "Powernerd",
        "Portugal. The Man", "Portishead", "Portico Quartet", "Porcupine Tree", "Poppy Ackroyd", "Placebo", "Pink Floyd", "Pim Stones", "Piano Peace",
        "Phosphorescent", "Phoria", "Phoebe Ryan", "Philharmonia Virtuosi of New York", "Phil France", "Pharrell Williams", "Phaeleh", "Peter and Kerry",
        "Peter Schmalfuss", "Peter Moore", "Peter Gregson", "Peter Dreimanis", "Pérotin", "Per Störby Jutbring", "Pell", "Peia", "Paul Simon",
        "Paul Cardall", "Patty Griffin", "Patti Smith", "Patrick Summers", "Patrick Carney", "Patricia Kopatchinskaja",
        "Pastor T.L. Barrett and the Youth for Christ Choir", "Pasquale Catalano", "Parson James", "Paris Texas", "Paolo Nutini", "Paolo Fresu",
        "Pablopavo i Ludziki", "PJ Harvey", "Özgür Yılmaz", "Outkast", "Otto Wahl", "Otto A. Totland", "Other Lives", "Osi And The Jupiter",
        "Örvar Smárason", "Orlando Julius", "Open Mike Eagle", "Oneohtrix Point Never", "OneRepublic", "One Two", "Omoh", "Omar Rodríguez-López",
        "Omar LinX", "Olivier Libaux", "Olivia Belli", "Old Man Canyon", "Ojerime", "Oh Wonder", "Odd Future", "Ocean Jet", "ORI", "ODESZA", "Nym",
        "Nudozurdo", "Norma Tanega", "Noname", "Noiserv", "Noel Gallagher's High Flying Birds", "Nine Inch Nails", "Nils Hoffmann", "Nile Rodgers",
        "Niklas Aman", "Nikki Jean", "Nights Amore", "Nigel North", "Nico Muhly", "Nicki Minaj", "Nick Cave & Warren Ellis", "Nick Cave & The Bad Seeds",
        "Nicholas Britell", "Netanel Goldberg", "Nervy", "Neon Nox", "Nelia Kit", "Neil Cowley", "Neat Beats", "Naxatras", "Nathan Lanier", "Nas",
        "Nappy Brown", "Naoki Kitaya", "Nandor Gotz", "Nakhane", "Nadine Shah", "Nadia Sirota", "NERVO", "M|O|O|N", "My Morning Jacket", "My Jerusalem",
        "Music Lab Collective", "Murray Head", "Mt. Wolf", "Mr. Probz", "Mr. Muthafuckin' eXquire", "Mozzy", "Mourning Ritual", "Mount Kimbie",
        "Moth Equals", "Morrissey", "Mooryc", "Moondog", "Monophona", "Mono Town", "Mondo Generator", "Monaldin", "Møme", "Moddi", "Mo' Horizons",
        "MiyaGi & Endspiel", "Mivos Quartet", "Miss Buttons", "Mirror System", "Mire.", "Minor Victories", "Mingue", "Milo Greene", "Mikky Ekko", "Mike G",
        "Miguel", "Midgar", "Michele Nobler", "Michael Nyman", "Michael McCann", "Michael Kiwanuka", "Mette Henriette", "Metropolitan Opera Orchestra",
        "Metro Boomin", "Metal Mother", "Merle Haggard", "Melvins", "Mélanie Pain", "Mélanie Laurent", "Melanie Faye", "Mel", "Megan Thee Stallion",
        "Mazzy Star", "Maxo Kream", "Max Joni", "Max Cooper", "Mauro Durante", "Matthew Mayfield", "Matt Haimovitz", "Matt Forbes", "Matt Berninger",
        "Mating Ritual", "Masquer", "Mashrou' Leila", "Mary Lambert", "Martin Stahl", "Marsha Ambrosius", "Mark Ronson", "Marjan Mozetich", "Mario Viñuela",
        "Mario Bernardi", "Marika Hackman", "Marian Hill", "Maria Due", "Mari Samuelsen", "Marek Hemmann", "Marcus Marr", "Marco Beltrami",
        "Marc Streitenfeld", "Mantu", "Manos Milonakis", "Manchester Orchestra", "Man of No Ego", "Malte Marten", "Malin My Wall", "Malik Djoudi", "Madeon",
        "Machete", "MOVEMENT", "MOTi", "MOMO", "MF DOOM", "MC Eiht", "M.I.A.", "M. Ostermeier", "Lunatic Soul", "Luis Flores", "Lucy Rose", "Lubomyr Melnyk",
        "Lowell Fulson", "Low", "Lovett", "Louisa Fuller", "Louis Armstrong", "Lotte Kestner", "Lost Souls Of Saturn", "Lost Frequencies", "Loscil",
        "Los Makenzy", "Lorne Balfe", "Lorenz Dangel", "Long Distance Calling", "London Electronic Orchestra", "Lole Y Manuel", "Logic", "Lizzy Land",
        "Little Simz", "Little May", "Little Cecil", "Little Barrie", "Lithuanian Chamber Orchestra", "Lisa Papineau", "Lisa Moore", "Lisa Gerrard",
        "Lisa Downing", "Linah Rocio & The Lighthearted", "Lil Silva", "Lil Dicky", "Lights & Motion", "Lights", "Library Tapes", "Liam Singer",
        "Lewis Del Mar", "Lévon Minassian", "Les Friction", "Leo Brouwer", "Lenny Ibizarre", "Lena Natalia", "Lee Moses", "Lee Hazlewood", "Lecrae",
        "Lazerhawk", "Layla", "Lawrence Taylor", "Lavinia Meijer", "Laura Welsh", "Laura Doggett", "Laura Corward", "Last Train", "Landon Tewers", "Lamb",
        "Lady Of The Sunshine", "Labrinth", "La Femme", "LAAKE", "L'Orchestra Cinematique", "Kyle Eastwood", "Kyle Bent", "Kyla La Grange", "Kojey Radical",
        "Kodaline", "Kisnou", "King Los", "King Gizzard & The Lizard Wizard", "Kina Grannis", "Kilo Kish", "Kid Wise", "Kid Francescoli", "Khebez Dawle",
        "Kenny Segal", "Kendra Logozar", "Keiko Matsui", "Katie Kim", "Kate Simko", "Kaskade", "Karin Borg", "Kapitan Korsakov", "Kammerorchester Basel",
        "Kamasi Washington", "Kaleida", "Kaki King", "KALEO", "Justin Townes Earle", "Justin Lockey", "Justin Coppolino", "Just Friends", "Jurdan Bryant",
        "Junkie XL", "Juliette Kang", "Julien Marchal", "Julian Lage", "Juice WRLD", "Jude Christodal", "Joshua Penman", "Joshua Chiu", "Josh Record",
        "Joseph Pepe Danza", "Jose Quezada Márquez", "Jorja Smith", "Jorge Méndez", "Jonny Greenwood", "Jon Batiste", "Joi", "Johnny", "John Zorn",
        "John Ozbay", "John Newman", "John Dowland", "John Abercrombie", "Johanna Dahl", "Johann Pachelbel", "Johan Berthling", "Jody Redhage",
        "Jo Stafford", "Jo Blankenburg", "Jiony", "Jhené Aiko", "Jessie Reyez", "Jesse Marchant", "Jesper Munk", "Jesper Kyd", "Jerzy Maksymiuk",
        "Jenő Jandó", "Jennifer Lawrence", "Jeff Russo", "Jeff Grace", "Jeff Bright Jr", "Jean-Pierre Taïeb", "Jean-Michel Blais", "Jay-Jay Johanson",
        "Jason Mraz", "Jason Molina", "Jasmine Thompson", "Jarryd James", "Jan Hammer", "Jan Blomqvist", "James Vincent McMorrow", "James Newton Howard",
        "James Heather", "James Brown", "James Blunt", "James Blake", "James Blackshaw", "Jakuzi", "Jaden", "Jack Liebeck", "Jack DeJohnette", "Jacaszek",
        "JOSEPH", "JONAH", "JID", "JG Thirlwell", "J.Views", "J. Ralph", "It's A Beautiful Day", "Isla", "Irving Force", "Irma Thomas", "Iosonouncane",
        "Intergalactic Lovers", "Inland Sky", "Ingrid Michaelson", "Illuminine", "Iko", "Iggy Pop", "Iggy Azalea", "Ibrahim Maalouf", "ISIS", "IOWA",
        "I Virtuosi Italiani", "Hypnogaja", "Huun-Huur-Tu", "Hugo", "Howling", "Howlin' Wolf", "Howard Shore", "How To Destroy Angels", "Holy Other",
        "Holy Holy", "Hollywood JB", "Hollow Coves", "Hollis", "Ho99o9", "Hinkstep", "Hillsong UNITED", "Hidden Citizens", "Henry Jackman", "Helmut",
        "Hannibal Buress", "Hannah Williams", "Hang Massive", "Hampshire & Foat", "Hail Mary Mallon", "HVOB", "HEALTH", "Gustavo Santaolalla", "Gui Boratto",
        "Groove Armada", "Groenland", "Grizzly Bear", "Gregory Alan Isakov", "Graveyard", "Gramatik", "Graham Nash", "Gorod 312", "Görkem Han", "Goldmund",
        "Goldfrapp", "GoldLink", "Gold & Youth", "God Is An Astronaut", "GoGo Penguin", "Glimmer of Blooms", "Glass Museum", "Glass Infinite",
        "Glass Cooperative", "Glass Animals", "Girls in Airports", "Giles Corey", "Gil Scott-Heron", "Gidon Kremer", "Giancarlo Paglialunga",
        "Gian Marco La Serra", "Ghostpoet", "Ghostlo", "Ghost Loft", "Gheorghe Zamfir", "Gernot Bronsert", "George Clinton", "Genghis Con", "Genevieve",
        "Gary Glitter", "Gary Girouard", "Gabriel Vitel", "Gábor Szabó", "GOSTO", "GOOSE", "G", "Future Islands", "Fuck Buttons", "French For Rabbits",
        "French 79", "Fredrika Stahl", "Frédéric Chopin", "Frankie Chavez", "Frank Wiedemann", "Francesco Taskayali", "Forget Gravity", "Flying Lotus",
        "Flume", "Florent Ghys", "Floating Points", "Flatbush Zombies", "Flash Forest", "Fischerspooner", "Fire! Orchestra", "Feverkin", "Fever Ray",
        "Feu! Chatterton", "Fennesz", "Fatlip", "Fatboy Slim", "Fanfara Tirana", "Fait", "Fabrizio De André", "FMLYBND", "FKA twigs", "FIL BO RIVA", "FARR",
        "F.S. Blumm", "Eyedea & Abilities", "Eyedea", "Evgeny Grinko", "Eva + Manu", "Eugene McGuinness", "Etta James", "Étienne de Crécy", "Erykah Badu",
        "Ernesto Schnack", "Erkan Oğur", "Eric Prydz", "Epic45", "Emmit Fenn", "Emma Ruth Rundle", "Emma Peters", "Emma Louise", "Emily Wells",
        "Emanuele Forni", "Emancipator", "Elvett", "Eluvium", "Else", "Elliott Wheeler", "Ella Fitzgerald", "Eliot Sumner", "Elijah Bossenbroek",
        "Elfa Run Kristinsdottir", "Elephant Tree", "Elektrik Haus", "Elcamino", "Einstürzende Neubauten", "Egotronic", "Efterklang", "Edwyn Collins",
        "Edward Sharpe & The Magnetic Zeros", "Edvard Grieg", "Ed Harcourt", "Easily Embarrassed", "EL VY", "EDX", "Dyve", "Duval Timothy",
        "Durand Jones & The Indications", "Dudu Aram", "Duckwrth", "Down Like Silver", "Douglas Firs", "Donovan Woods", "Donovan", "Dom", "Doctor Flake",
        "Djivan Gasparyan", "Dirty South", "Dirk Mallwitz", "Direct", "Dinnerdate", "Diego Luna", "Dickon Hinchliffe", "Dick Dale", "Diamante Eléctrico",
        "Dhafer Youssef", "Desmond Cheese", "Desert Sessions", "Denzel Curry", "Denez Prigent", "Den Sorte Skole", "Delta Spirit", "Delaney Jane",
        "Deep Sleep", "Dean Hudson & His Orchestra", "Dead Meadow", "Dax Riggs", "Dawn Golden", "David Vertesi", "David Gilmour", "Dave Thomas Junior",
        "Dave Porter", "Darondo", "Darkstar", "Dark Sanctuary", "Danny Cudd", "Danish National Chamber Orchestra", "Daniele di Bonaventura",
        "Daniela Andrade", "Daniel Licht", "Daniel Ketchum", "Daniel Johnston", "Dan Deacon", "DakhaBrakha", "DYAN", "DNKL", "DJ Abilities", "DAVID AUGUST",
        "Cœur De Pirate", "Curtis Mayfield", "Cults", "Culprate", "Crown The Empire", "Crooked Colours", "Crippled Black Phoenix",
        "Creedence Clearwater Revival", "Copenhagen Phil", "Conrad Sewell", "Conor Walsh", "Conner Youngblood", "Concorde", "Colleen D'agostino", "Colleen",
        "Cole Alexander", "Colbie Caillat", "Cody Chesnutt", "Codes In The Clouds", "Cocc Pistol Cree", "Clyde McPhatter", "Clogs", "ClickClickDecker",
        "Classical Music Radio", "Clara Oaks", "Claire Pichet", "Civil Twilight", "Citizens!", "Chris Corner", "Chris Coco", "Chinese Man",
        "Chilly Gonzales", "Chet Faker", "Cherry Dragon", "Chelsea Wolfe", "Charlotte OC", "Charlie Musselwhite", "Charlie Cunningham", "Charles Brown",
        "Catrin Finch", "Caspian", "Casey Veggies", "Casey Hensley", "Carly Comando", "Carlos Cipa", "Carina Round", "Cardi B", "Car Seat Headrest",
        "Captain Murphy", "Cappo", "Cannibal Ox", "Camille Yarbrough", "Camille", "Cameron the Public", "Camerata Bern", "Camel Power Club", "Calvin Harris",
        "Cakes da Killa", "Cage The Elephant", "Cactus?", "CBC Vancouver Orchestra", "CAKE", "C418", "C2C", "Bytheway-May", "Bun B", "Bugzy Malone",
        "Buck 65", "Bryde", "Bryce Dessner", "Brutus", "Bruno Coulais", "Bruce Springsteen", "Bror Gunnar Jansson", "Broken Iris", "Britt Nicole",
        "Brigitte", "Brice Davoli", "Brian Tyler", "Brian Eno", "Brian Crain", "Brent Faiyaz", "Break of Reality", "Brasstronaut", "Brant Bjork", "Brambles",
        "Braids", "Booker T. & the M.G.'s", "Bonaparte", "Bohren & Der Club Of Gore", "Bohdi", "Bobby Womack", "Bobby Bazini", "Bobbie Gentry", "Bob Moses",
        "Bob Dylan", "Boards of Canada", "Blues Pills", "Blue Öyster Cult", "Blue Hawaii", "Blood, Sweat & Tears", "Blixa Bargeld", "Blind Willie Johnson",
        "Blind Pilot", "Blended Babies", "Blakroc", "Black Mountain", "Black Milk", "Black Math", "Björn Baummann", "Bishop G", "Billy Paul", "Billy Boyd",
        "Bill Ryder-Jones", "Big Boi", "Beyries", "Benny The Butcher", "Benjamin Wallfisch", "Benjamin Francis Leftwich", "Benj Heard", "Ben Lukas Boysen",
        "Ben Goldwasser", "Ben Frost", "Ben Bridwell", "Bell Witch", "Belako", "Bear's Den", "Beady Eye", "Beacon", "Bastien Keb", "Bang Gang", "Balthazar",
        "Balazs Szokolay", "Bad Omens", "Baby Huey", "Baby Bee", "Babx", "Baauer", "BUSDRIVER", "BUFFLO", "BROODS", "BLOW", "BLOND:ISH",
        "BBC Scottish Symphony Orchestra", "BADBADNOTGOOD", "Azad Sesh", "Ayyuka", "Aylior", "Austin Wintory", "Audrey Fall", "Au4", "Asaf Avidan",
        "Arvo Pärt", "Ariana Grande", "Arenna", "Ardie Son", "Arcade Fire", "Aquilo", "Apparat", "Aphrodite's Child",
        "Aperture Science Psychoacoustic Laboratories", "Antwon", "Antony Pitts", "Anthony Weeden", "Anouar Brahem", "Anoice", "Anne Müller",
        "Anna Netrebko", "Angus Stone", "Angus MacRae", "Andrew VanWyngarden", "Andrew Skeet", "Andrew Lloyd Webber", "Andrew Jasinski",
        "Andreas Söderström", "Amy Rose", "Aminé", "Amigo the Devil", "Âme", "Amber Coffman", "Amarante", "Aloe Blacc", "Allen Stone", "Allan Rayman",
        "Allan Kingdom", "Alice on the roof", "Alice Cooper", "Alice Baldwin", "Alfred Schnittke", "Alexi Murdoch", "Alexandra Streliski", "Alex Gaudino",
        "Alex Clare", "Alessandra Celletti", "Alec Troniq", "Albin Lee Meldau", "Alberto Giurioli", "Alan Watts", "Alala", "Alain Goraguer", "Al Lover",
        "Akira Kosemura", "Air Lyndhurst String Orchestra", "Aimee Mann", "Aidan Hawken", "Agar Agar", "Aereogramme", "Adventure Club", "Adele Anthony",
        "Adam Jensen", "Açık Seçik Aşk Bandosu", "Action Bronson", "Ace Hood", "Aaron Krause", "Aaron Jerome", "AWOLNATION", "ALISA UENO", "A.A. Williams",
        "A Winged Victory for the Sullen", "A Whisper in the Noise", "A Fine Frenzy", "A Filetta", ":Of The Wand And The Moon:", "6LACK", "65daysofstatic",
        "50 Cent", "414Beg", "21 Savage", "17 Hippies", "123", "1000mods", "10 Years", "...And You Will Know Us by the Trail of Dead", "*shels",
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
        'downloader_api_url'       => env('DEEMIX_DOWNLOADER_API_URL', ''),
        'downloads_folder'         => env('DEEMIX_DOWNLOADS_FOLDER', 'Music'),
        'downloads_bitrate'        => env('DEEMIX_DOWNLOADS_BITRATE', 'flac'),
        // to rewrite download folder name in links
        'downloads_folder_rewrite' => env('DEEMIX_DOWNLOADS_FOLDER_REWRITE', 'flacs'),
    ],
];
