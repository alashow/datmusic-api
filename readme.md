# datmusic-api ya-api

This branch contains a wrapper for api.я.ws (which is itself a wrapper for VK Audio API :)).

It works just as datmusic-api from master branch, but used api.я.ws to get audios.

# Setup

Set your ya-api key in `.env` file.

# Search

Search results are cached for 24 hours by default.

`https://example.com/search?q={query}&page={page}`

# Downloads & Streams

`https://example.com/dl/{search_hash}/{audio_hash}` (force download with proper file name (`Artist - Title.mp3`))

`https://example.com/stream/{search_hash}/{audio_hash}` (redirects to mp3 file)

`https://example.com/bytes/{search_hash}/{audio_hash}` (returns file size of mp3 in bytes)

# Bitrate converting

Default convertable bitrates are: `64`, `128`, `192`
You need to install `ffmpeg` to your server to make it work and change path to binary in [config file](config/app.php).
 
`https://example.com/dl/{search_hash}/{audio_hash}/{bitrate}`
`https://example.com/stream/{search_hash}/{audio_hash}/{bitrate}`

# Hashing

Search hash calculated by request params (query and page).
Audio hash calculated by audio id.
Default hashing algorithm is [`crc32`](https://en.wikipedia.org/wiki/Cyclic_redundancy_check). I chose this because of speed, short length, and I didn't need cryptographic hashing. You can change it in config if you want. 
 
# Cache

As far as I know, mp3 urls of VK songs are valid only for 24 hours. So we can cache search results only for 24 hours.

By default, when using S3 as storage, mp3 files can be cached forever (as long as mp3 file is present in bucket). 

Default caching driver is `files`. Thanks to [Laravel Cache](https://laravel.com/docs/5.3/cache) system, it can be easily configured to different cache drivers.
Redis cache driver is configured. Just change driver and set credentials in .env.
 
# Using with S3 Storage
 
You can enable or disable S3 storage option in [config file](config/app.php) (enabled by default).

When it's enabled, mp3 files will be downloaded to s3 bucket instead of local disk.

Download/stream links will be redirected to S3 servers.

# Deployment

Follow instructions described in [here](https://goo.gl/gK73JE).
or see short version in this [comment](https://github.com/alashow/datmusic-api/issues/2#issuecomment-275946684);

Please browse code or open an issue to understand more. 

Open an issue or contact me at me@alashov.com for help with deployment.

## License

    Copyright (C) 2017  Alashov Berkeli

        This program is free software: you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation, either version 3 of the License, or
        (at your option) any later version.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program.  If not, see <http://www.gnu.org/licenses/>.
