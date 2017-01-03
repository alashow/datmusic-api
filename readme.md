# datmusic-api

This is new api for datmusic which uses directly m.vk.com (not api) to get audio data.

I made this because VK disabled their public Audio API access.
 
# How it works
It's written with [Lumen](https://lumen.laravel.com), micro web framework backed by [Laravel](https://laravel.com)
  
The app logins to site using credentials from [.env](.env.example) or [config file](config/app.php#L20) (multiple accounts supported) and saves cookies in disk for reusing.
Then it searches songs with given query in vk website and parses it to an array. It will save parsed data in cache and will return json to user.

# Cache

As far as I know, mp3 urls of Vk songs are valid only for 24 hours. So we can cache data only for 24 hours.

# Downloads & Streams

`https://api.datmusic.xyz/{search_hash}{audio_hash}` (force download with proper file name (`Artist - Title.mp3`))

`https://api.datmusic.xyz/stream/{search_hash}{audio_hash}` (redirects to mp3 file)

`https://api.datmusic.xyz/bytes/{search_hash}{audio_hash}` (returns file size of mp3 in bytes)

Search hash calculated by request params (query and page).
Audio hash calculated by mp3 url.
Default hashing algorithm is [`alder32`](https://en.wikipedia.org/wiki/Adler-32). I chose this because of speed, short length, and I didn't need cryptographic hashing. You can change it in config if you want.

In web version of VK, there is not page similar to [audio.get](https://vk.com/dev/audio.get), so we can only serve and stream mp3 files that are shown by search function (it searches hashes from cache). 


Please browse code to understand more. 

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