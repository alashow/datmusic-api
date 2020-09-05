<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use JamesHeinrich\GetID3\GetID3;
use JamesHeinrich\GetID3\WriteTags;
use Log;

trait DownloaderTrait
{
    /**
     * DownloaderTrait constructor.
     */
    public function bootDownloader()
    {
    }

    /**
     * Get size of audio file in bytes.
     *
     * @param $key
     * @param $id
     *
     * @return int
     */
    public function bytes($key, $id)
    {
        logger()->log('Bytes', $key, $id);

        $cacheKey = "bytes_$id";

        // get from cache or store in cache and return value
        return Cache::rememberForever($cacheKey, function () use ($key, $id) {
            $path = $this->buildFilePathsForId($id)[2];
            if (@file_exists($path)) {
                return filesize($path);
            }

            $item = $this->getAudio($key, $id);
            if ($this->optimizeMp3Url($item)) {
                return get_headers($item['mp3'], 1)['Content-Length'];
            }

            $response = httpClient()->head($item['mp3']);

            return $response->getHeader('Content-Length')[0];
        });
    }

    /**
     * Just like download but with stream enabled.
     *
     * @param string $key
     * @param string $id
     *
     * @return RedirectResponse
     */
    public function stream($key, $id)
    {
        return $this->download($key, $id, true);
    }

    /**
     * Just like download but with bitrate converting enabled.
     *
     * @param string $key
     * @param string $id
     * @param int    $bitrate
     *
     * @return RedirectResponse
     */
    public function bitrateDownload($key, $id, $bitrate)
    {
        return $this->download($key, $id, false, $bitrate);
    }

    /**
     * Serves given audio item or aborts with 404 if not found.
     *
     * @param string $key
     * @param string $id
     * @param bool   $stream
     * @param int    $bitrate
     *
     * @return RedirectResponse
     */
    public function download($key, $id, $stream = false, $bitrate = -1)
    {
        if (! in_array($bitrate, config('app.conversion.allowed'))) {
            $bitrate = -1;
        }

        [$fileName, $localPath, $path] = $this->buildFilePathsForId($id);

        if (@file_exists($path)) {
            $item = $this->getAudioCache($id);
            // try looking in search cache if not found
            if (is_null($item)) {
                $item = $this->getAudio($key, $id, false);
            }
            $name = ! is_null($item) ? $this->getFormattedName($item) : "$id.mp3";

            $this->tryToConvert($bitrate, $path, $localPath, $fileName, $name);

            return $this->downloadLocal($path, $fileName, $key, $id, $name, $stream, true);
        }

        $item = $this->getAudio($key, $id);
        $proxy = ! $this->optimizeMp3Url($item);
        $name = $this->getFormattedName($item);

        if ($this->downloadFile($item['mp3'], $path, $proxy)) {
            $this->writeAudioTags($item, $path);
            $this->tryToConvert($bitrate, $path, $localPath, $fileName, $name);

            return $this->downloadLocal($path, $fileName, $key, $id, $name, $stream, false);
        } else {
            abort(500);
        }
    }

    /**
     * Download/Stream local file.
     *
     * @param $path     string full path
     * @param $fileName string file name
     * @param $key      string search key
     * @param $id       string audio id
     * @param $name     string download response name
     * @param $stream   boolean  is stream
     * @param $cache    boolean is cache
     *
     * @return RedirectResponse
     */
    private function downloadLocal($path, $fileName, $key, $id, $name, $stream, $cache)
    {
        if ($stream) {
            logger()->stream($cache, $key, $id);

            return redirect("mp3/$fileName");
        } else {
            logger()->download($cache, $key, $id);

            return $this->downloadResponse($path, $name);
        }
    }

    /**
     * Try to convert mp3 if possible, alters given path and file path if succeeds.
     *
     * @param $bitrate   int bitrate
     * @param $path      string path
     * @param $localPath string local file path
     * @param $fileName  string file name
     * @param $name      string file name (logging)
     */
    private function tryToConvert($bitrate, &$path, $localPath, &$fileName, &$name)
    {
        $convertResult = $this->bitrateConvert($bitrate, $path, $localPath, $fileName);

        if ($convertResult != false) {
            [$fileName, $path] = $convertResult;
            logger()->convert($name, $bitrate);
            $name = str_replace('.mp3', " ($bitrate).mp3", $name);
        }
    }

    /**
     * @param $bitrate
     * @param $path
     * @param $localPath
     * @param $fileName
     *
     * @return array|bool
     */
    private function bitrateConvert($bitrate, $path, $localPath, $fileName)
    {
        if ($bitrate > 0) {
            // Change path only if already converted or conversion function returns true
            $pathConverted = $this->formatPathWithBitrate($localPath, $bitrate);
            $fileNameConverted = $this->formatPathWithBitrate($fileName, $bitrate);

            if (file_exists($pathConverted) || $this->convertMp3Bitrate($bitrate, $localPath, $pathConverted)) {
                $convertedPaths = [$fileNameConverted, $pathConverted];
            }
        }

        return isset($convertedPaths) ? $convertedPaths : false;
    }

    /**
     * Formats name, appends mp3, ascii-fy and remove bad characters.
     *
     * @param array $item
     *
     * @return string formatted name
     */
    private function getFormattedName($item)
    {
        $name = sprintf('%s - %s', $item['artist'], $item['title']);
        $name = Str::ascii($name);
        $name = sanitize($name, false, false);
        $name = sprintf('%s.mp3', $name);

        return $name;
    }

    /**
     * Build file name and full path for given audio id.
     *
     * @param string $id audio id
     *
     * @return array 0 - file name, 1 - full local path, 2 - full local path
     */
    private function buildFilePathsForId($id)
    {
        $hash = hash(config('app.hash.mp3'), $id);
        $subPath = sprintf('%s/%s/%s', config('app.paths.mp3'), substr($hash, 0, 2), substr($hash, 2, 2));
        $fileName = sprintf('%s.mp3', hash(config('app.hash.mp3'), $id));
        $localPath = sprintf('%s/%s', $subPath, $fileName);
        $path = $localPath;

        if (! is_dir($subPath)) {
            @mkdir($subPath, 0755, true);
        }

        return [$fileName, $localPath, $path];
    }

    /**
     * @param string $path    path to mp3
     * @param int    $bitrate bitrate
     *
     * @return string path_bitrate.mp3 formatted path
     */
    private function formatPathWithBitrate($path, $bitrate)
    {
        if ($bitrate > 0) {
            return str_replace('.mp3', "_$bitrate.mp3", $path);
        } else {
            return $path;
        }
    }

    /**
     * Download given file url to given path.
     *
     * @param string $url
     * @param string $path
     * @param bool   $proxy
     *
     * @return bool true if succeeds
     */
    private function downloadFile($url, $path, $proxy = true)
    {
        $handle = fopen($path, 'w');

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FILE, $handle);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, config('app.downloading.timeout.connection'));
        curl_setopt($curl, CURLOPT_TIMEOUT, config('app.downloading.timeout.execution'));

        if ($proxy && env('PROXY_ENABLE', false)) {
            curl_setopt($curl, CURLOPT_PROXY, env('PROXY_IP'));
            curl_setopt($curl, CURLOPT_PROXYPORT, env('PROXY_PORT'));
            curl_setopt($curl, CURLOPT_PROXYTYPE, env('PROXY_METHOD'));

            if (! empty(env('PROXY_USERNAME')) && ! empty(env('PROXY_PASSWORD'))) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, sprintf('%s:%s', env('PROXY_USERNAME'), env('PROXY_PASSWORD')));
            }
        }

        curl_exec($curl);

        // if curl had errors
        if (curl_errno($curl) > 0) {
            logger()->log('Download.Fail', curl_errno($curl));

            // remove the file just in case
            @unlink($path);

            return false;
        }

        //close files
        curl_close($curl);
        fclose($handle);

        return true;
    }

    /**
     * Creates symlink to original mp3 file with given file name at /links/{mp3_hash}/{name}.
     * For now, we are getting mp3 hash from file name of given path.
     *
     * @param $path string path of the file
     * @param $name string name of the downloading file
     *
     * @return RedirectResponse
     */
    private function downloadResponse($path, $name)
    {
        $fileName = basename($path, '.mp3');
        $filePath = sprintf('%s/%s', $fileName, $name);
        $linkFolderPath = sprintf('%s/%s', config('app.paths.links'), $fileName);
        $linkPath = sprintf('%s/%s', $linkFolderPath, $name);

        if (file_exists($linkPath) || ((file_exists($linkFolderPath) || mkdir($linkFolderPath, 0777)) && symlink($path, $linkPath))) {
            return redirect("links/$filePath");
        }

        abort(500, "Couldn't create symlink for downloading");
    }

    /**
     * Try to write mp3 id3 tags.
     *
     * @param $audio array an array with fields title and artist
     * @param $path  string full path to file
     */
    private function writeAudioTags($audio, $path)
    {
        try {
            $encoding = 'UTF-8';
            $getID3 = new GetID3();
            $getID3->setOption(['encoding' => $encoding]);
            $writer = new WriteTags();
            $writer->filename = $path;
            $writer->tagformats = ['id3v1', 'id3v2.3'];
            $writer->remove_other_tags = false;
            $writer->tag_encoding = $encoding;

            $tags = [
                'title'   => [$audio['title']],
                'artist'  => [$audio['artist']],
                'comment' => [config('app.downloading.id3.comment')],
            ];
            if (array_key_exists('album', $audio)) {
                $tags = array_merge($tags, [
                    'album' => [$audio['album']],
                ]);
            }
            if (config('app.downloading.id3.download_covers')) {
                if ($coverImage = covers()->getImageFile($audio)) {
                    if ($coverImageFile = file_get_contents($coverImage)) {
                        if ($coverImageExif = exif_imagetype($coverImage)) {
                            $tags['attached_picture'][0]['data'] = $coverImageFile;
                            $tags['attached_picture'][0]['mime'] = image_type_to_mime_type($coverImageExif);
                            $tags['attached_picture'][0]['picturetypeid'] = 0x03;
                            $tags['attached_picture'][0]['description'] = 'cover';
                        } else {
                            Log::error('Unable to read cover image exif while trying to write to mp3', [$coverImage]);
                        }
                    } else {
                        Log::error('Unable to read cover image file while trying to write to mp3', [$coverImage]);
                    }
                    @unlink($coverImage);
                }
            }
            $writer->tag_data = $tags;
            $writer->WriteTags();
        } catch (\getid3_exception $e) {
            Log::error('Exception while writing id3 tags', [$audio, $path, $e]);
        }
    }

    /**
     * Executes ffmpeg command synchronously for converting given file to given bitrate.
     *
     * @param $bitrate integer, one of $config["allowed_bitrates"]
     * @param $input   string input mp3 file full path
     * @param $output  string output mp3 file full path
     *
     * @return bool is success
     */
    private function convertMp3Bitrate($bitrate, $input, $output)
    {
        $bitrateString = config('app.conversion.allowed_ffmpeg')[array_search($bitrate,
            config('app.conversion.allowed'))];
        $ffmpegPath = config('app.conversion.ffmpeg_path');

        exec("$ffmpegPath -i $input -codec:a libmp3lame $bitrateString $output", $exOutput,
            $result);

        return $result == 0;
    }
}
