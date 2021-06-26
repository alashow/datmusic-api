<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use App\Jobs\PostProcessAudioJob;
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
    public function bytes(string $key, string $id)
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

            $response = vkClient()->head($item['mp3']);

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
    public function stream(string $key, string $id)
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
    public function bitrateDownload(string $key, string $id, int $bitrate)
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
     * @return RedirectResponse|void
     */
    public function download(string $key, string $id, $stream = false, $bitrate = -1)
    {
        if (! in_array($bitrate, config('app.conversion.allowed'))) {
            $bitrate = -1;
        }

        [$fileName, $subPath, $path] = $this->buildFilePathsForId($id);

        if (@file_exists($path)) {
            $audioItem = $this->getCachedAudio($id);
            // try looking in search cache if not found
            if (is_null($audioItem)) {
                $audioItem = $this->getAudio($key, $id, false);
            }
            $name = ! is_null($audioItem) ? $this->getFormattedName($audioItem) : "$id.mp3";

            $this->tryToConvert($bitrate, $path, $fileName, $name);

            return $this->downloadLocal($path, $subPath, $fileName, $key, $id, $name, $stream, true);
        }

        $audioItem = $this->getAudio($key, $id);
        $proxy = ! $this->optimizeMp3Url($audioItem);
        $name = $this->getFormattedName($audioItem);

        if ($this->downloadAudio($audioItem['mp3'], $path, $proxy, $audioItem)) {
            $this->writeAudioTags($audioItem, $path);
            $this->onDownloadCallback($audioItem);
            // TODO: remove bitrate conversion feature
            $this->tryToConvert($bitrate, $path, $fileName, $name);

            return $this->downloadLocal($path, $subPath, $fileName, $key, $id, $name, $stream, false);
        } else {
            abort(500);
        }
    }

    /**
     * Download/Stream local file.
     *
     * @param $path     string full path
     * @param $subPath  string sub path
     * @param $fileName string file name
     * @param $key      string search key
     * @param $id       string audio id
     * @param $name     string download response name
     * @param $stream   boolean  is stream
     * @param $cache    boolean is cache
     *
     * @return RedirectResponse
     */
    private function downloadLocal(string $path, string $subPath, string $fileName, string $key, string $id, string $name, bool $stream, bool $cache)
    {
        if ($stream) {
            logger()->stream($cache, $key, $id);

            return redirect("mp3/$subPath/$fileName");
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
     * @param $fileName  string file name
     * @param $name      string file name (logging)
     */
    private function tryToConvert(int $bitrate, string &$path, string &$fileName, string &$name)
    {
        $convertResult = $this->bitrateConvert($bitrate, $path, $fileName);

        if ($convertResult != false) {
            [$fileName, $path] = $convertResult;
            logger()->convert($name, $bitrate);
            $name = str_replace('.mp3', " ($bitrate).mp3", $name);
        }
    }

    /**
     * @param int    $bitrate
     * @param string $path
     * @param string $fileName
     *
     * @return array|bool
     */
    private function bitrateConvert(int $bitrate, string $path, string $fileName)
    {
        if ($bitrate > 0) {
            // Change path only if already converted or conversion function returns true
            $pathConverted = $this->formatPathWithBitrate($path, $bitrate);
            $fileNameConverted = $this->formatPathWithBitrate($fileName, $bitrate);

            if (file_exists($pathConverted) || $this->convertMp3Bitrate($bitrate, $path, $pathConverted)) {
                $convertedPaths = [$fileNameConverted, $pathConverted];
            }
        }

        return $convertedPaths ?? false;
    }

    /**
     * Formats name, appends mp3, ascii-fy and remove bad characters.
     *
     * @param array $audio
     *
     * @return string formatted name
     */
    private function getFormattedName(array $audio)
    {
        $name = sprintf('%s - %s', $audio['artist'], $audio['title']);
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
     * @return array 0 - file name, 1 - sub path, 2 - full local path
     */
    private function buildFilePathsForId(string $id)
    {
        $hash = hash(config('app.hash.mp3'), $id);
        $subPath = subPathForHash($hash);
        $fileName = sprintf('%s.mp3', $hash);
        $path = sprintf('%s/%s', config('app.paths.mp3'), $subPath);

        if (! is_dir($subPath)) {
            @mkdir($path, 0755, true);
        }

        $path = sprintf('%s/%s', $path, $fileName);

        return [$fileName, $subPath, $path];
    }

    /**
     * @param string $path    path to mp3
     * @param int    $bitrate bitrate
     *
     * @return string path_bitrate.mp3 formatted path
     */
    private function formatPathWithBitrate(string $path, int $bitrate)
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
     * @param array  $audioItem
     *
     * @return bool true if succeeds
     */
    private function downloadAudio(string $url, string $path, bool $proxy = true, $audioItem = [])
    {
        if (! file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
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
            @unlink($path);

            return false;
        }

        // or the file is not audio
        $fileMimeType = get_mime_type($path);
        if (! isMimeTypeAudio($fileMimeType)) {
            logger()->log('Download.Fail.InvalidAudio', $fileMimeType);
            @unlink($path);

            return false;
        }

        //close files
        curl_close($curl);
        fclose($handle);

        return true;
    }

    /**
     * Creates symlink to original mp3 file with given file name at /links/{sub_path}/{mp3_hash}/{name}.
     * For now, we are getting mp3 hash from file name of given path.
     *
     * @param $path string path of the file
     * @param $name string name of the downloading file
     *
     * @return RedirectResponse|void
     */
    private function downloadResponse(string $path, string $name)
    {
        $hash = basename($path, '.mp3');
        $subPath = subPathForHash($hash);
        $filePath = sprintf('%s/%s', $hash, $name);
        $linkFolderPath = sprintf('%s/%s/%s', config('app.paths.links'), $subPath, $hash);
        $linkPath = sprintf('%s/%s', $linkFolderPath, $name);

        if (file_exists($linkPath) || ((file_exists($linkFolderPath) || mkdir($linkFolderPath, 0777, true)) && symlink($path, $linkPath))) {
            return redirect("links/$subPath/$filePath");
        }

        abort(500, "Couldn't create symlink for downloading");
    }

    /**
     * Try to write mp3 id3 tags.
     *
     * @param $audio array an array with fields title and artist
     * @param $path  string full path to file
     */
    private function writeAudioTags(array $audio, string $path)
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
            $downloadCovers = config('app.downloading.id3.download_covers');
            if ($downloadCovers) {
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
    private function convertMp3Bitrate(int $bitrate, string $input, string $output)
    {
        $bitrateString = config('app.conversion.allowed_ffmpeg')[array_search($bitrate,
            config('app.conversion.allowed'))];
        $ffmpegPath = config('app.conversion.ffmpeg_path');

        exec("$ffmpegPath -i $input -codec:a libmp3lame $bitrateString $output", $exOutput,
            $result);

        return $result == 0;
    }

    /**
     * Dispatch post process audio job if enabled.
     *
     * @param array $audioItem audio item info
     */
    private function onDownloadCallback(array $audioItem)
    {
        if (config('app.downloading.post_process.enabled')) {
            dispatch(new PostProcessAudioJob($audioItem))->onQueue('post_process');
        }
    }
}
