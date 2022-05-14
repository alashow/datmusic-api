# datmusic-api

# Endpoints
### Search

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

Get audios by album - `https://example.com/albums/{album_id}`

### Downloads & Streams

- Download (`Artist - Title.mp3`) - `https://example.com/dl/{search_hash}/{audio_hash}`
- Stream - `https://example.com/stream/{search_hash}/{audio_hash}`

## License

    Copyright (C) 2022  Alashov Berkeli

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
