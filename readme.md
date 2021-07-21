# datmusic-api vk-api

This branch contains a wrapper for VK API.

VK's private audio APIs can be used only with tokens from official apps or special third party apps (for ex. Kate mobile).
User agent of that app must be used when talking to VK when using such tokens.
 
# How it works
It's written using [Lumen](https://lumen.laravel.com), micro web framework by [Laravel](https://laravel.com)
  
The wrapper searches via API, caches the results. Tries to recover from captchas.
Everything else is same as in original datmusic-api.

# How to get tokens

See https://github.com/vodka2/vk-audio-token

# Endpoints
### Search
By default, audio and album/artist search results are cached for 24 hours and a week, respectively.

- Audio search - `https://example.com/search?q={query}&page={page}`
- Artists search - `https://example.com/search/artists?q={query}`
- Albums search - `https://example.com/search/albums?q={query}`
    - Querying artist name will return artist's all albums


### Multisearch

Search multiple backends at once

- Available types: `audios`, `artists`, `albums` (defaults to only `audios`)
- `https://example.com/multisearch?q={query}&page={page}&types[]=audios&types[]=artists`

### Artists

- Get audios by artist - `https://example.com/artists/{artist_id}`
- Get albums by artist - `https://example.com/artists/{artist_id}/albums`

### Albums
This endpoint will require extra parameters returned by albums search, `owner_id` and `access_key`. 

Get audios by album - `https://example.com/albums/{album_id}?owner_id={owner_id}&access_key={access_key}`

# Downloads & Streams

- Force download with proper file name (`Artist - Title.mp3`) - `https://example.com/dl/{search_hash}/{audio_hash}`
- Redirects to mp3 file - `https://example.com/stream/{search_hash}/{audio_hash}`
- Get file size of mp3 in bytes - `https://example.com/bytes/{search_hash}/{audio_hash}`

# Hashing

Search hash calculated by request params (query and page).
Audio hash calculated by audio id and owner id.
Default hashing algorithm is [`crc32`](https://en.wikipedia.org/wiki/Cyclic_redundancy_check). I chose this because of speed, short length, and I didn't need cryptographic hashing. You can change it in config if you want.

# Cache

As far as I know, mp3 urls of VK songs are valid only for 24 hours. So we can cache search results only for 24 hours. 

Default caching driver is `files`. Thanks to [Laravel Cache](https://laravel.com/docs/6.x/cache) system, it can be easily configured to different cache drivers.
Redis cache driver is configured. Just change driver and set credentials in .env.

# Errors
In vk-api branch, you can get errors in search responses.
Captchas are the only recoverable errors.
Example captcha error response:
```json
{
  "status": "error",
  "error": {
    "message": "Captcha!",
    "captcha_index": 1,
    "captcha_id": 123456789,
    "captcha_img": "https://url-to-captcha"
  }
}
```

Client app will need to show `captcha_img` to user, and then retry search request with 3 additional queries:
1. `captcha_index` returned captcha index.
2. `captcha_id` returned captcha id.
3. `captcha_key` user's answer to shown `captcha_img` captcha.

`https://example.com/search?q={query}&page={page}&captcha_index={captcha index}&captcha_id={captcha id}&captcha_key={captcha answer}`

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
