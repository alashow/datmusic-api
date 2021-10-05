<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use App\Jobs\PostProcessAudioJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * @param  Request  $request
     * @param  string  $key
     * @param  string  $id
     * @return RedirectResponse
     */
    public function stream(Request $request, string $key, string $id)
    {
        return $this->download($request, $key, $id, true);
    }

    /**
     * Serves given audio item or aborts with 404 if not found.
     *
     * @param  Request  $request
     * @param  string  $key
     * @param  string  $id
     * @param  bool  $stream
     * @return RedirectResponse
     */
    public function download(Request $request, string $key, string $id, bool $stream = false)
    {
        if ($this->isDeemixId($id)) {
            return $this->deemixDownload($request, $id, $stream);
        }

        $isRedirect = $request->has('redirect');
        [$fileName, $subPath, $path] = $this->buildFilePathsForId($id);

        if (@file_exists($path)) {
            $audioItem = $this->getCachedAudio($id);
            // try looking in search cache if not found
            if (is_null($audioItem)) {
                $audioItem = $this->getAudio($key, $id, false);
            }
            $name = ! is_null($audioItem) ? $this->getFormattedName($audioItem) : "$id.mp3";

            return $this->downloadLocal($path, $subPath, $fileName, $key, $id, $name, $stream, true, $isRedirect);
        }

        $fetchNewMp3Url = $key === self::$SEARCH_BACKEND_MINERVA;

        $audioItem = $this->getAudio($key, $id, true, $fetchNewMp3Url);
        $proxy = ! $this->optimizeMp3Url($audioItem);
        $name = $this->getFormattedName($audioItem);

        if ($this->downloadAudio($audioItem['mp3'], $path, $proxy, $audioItem)) {
            $this->writeAudioTags($audioItem, $path);
            $this->onDownloadCallback($audioItem);

            return $this->downloadLocal($path, $subPath, $fileName, $key, $id, $name, $stream, false, $isRedirect);
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
     * @param $redirect boolean is redirect
     * @return RedirectResponse
     */
    private function downloadLocal(string $path, string $subPath, string $fileName, string $key, string $id, string $name, bool $stream, bool $cache, bool $redirect)
    {
        if ($stream) {
            logger()->stream($cache, $key, $id, $name);

            return redirect("mp3/$subPath/$fileName");
        } else {
            logger()->download($cache, $key, $id, $name, $redirect);
            if ($redirect) {
                return redirect("mp3/$subPath/$fileName");
            }

            return $this->downloadResponse($path, $name);
        }
    }

    /**
     * Creates symlink to original mp3 file with given file name at /links/{sub_path}/{mp3_hash}/{name}.
     * For now, we are getting mp3 hash from file name of given path.
     *
     * @param $path string path of the file
     * @param $name string name of the downloading file
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
     * Formats name, appends mp3, ascii-fy and remove bad characters.
     *
     * @param  array  $audio
     * @return string formatted name
     */
    private function getFormattedName(array $audio)
    {
        $name = sprintf('%s - %s', $audio['artist'], $audio['title']);
        $name = Str::ascii($name);
        $name = sanitize($name, false, false);

        return sprintf('%s.mp3', $name);
    }

    /**
     * Build file name and full path for given audio id.
     *
     * @param  string  $id  audio id
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
     * Download given file url to given path.
     *
     * @param  string  $url
     * @param  string  $path
     * @param  bool  $proxy
     * @param  array  $audioItem
     * @return bool true if succeeds
     */
    private function downloadAudio(string $url, string $path, bool $proxy = true, array $audioItem = [])
    {
        if (! file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        if (config('app.downloading.hls.enabled') && array_key_exists('is_hls', $audioItem) && $audioItem['is_hls']) {
            return $this->downloadAudioFfmpeg($url, $path, $proxy, $audioItem);
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

        if (! $this->verifyDownloadedFile($path, $audioItem)) {
            return false;
        }

        //close files
        curl_close($curl);
        fclose($handle);

        return true;
    }

    /**
     * Download audio using ffmpeg.
     *
     * @param  string  $url
     * @param  string  $path
     * @param  bool  $proxy
     * @param  array  $audioItem
     * @return bool
     */
    private function downloadAudioFfmpeg(string $url, string $path, bool $proxy = true, array $audioItem = [])
    {
        $startedAt = microtime(true);
        $ffmpeg = config('app.tools.ffmpeg_path');
        if ($proxy && env('PROXY_ENABLE', false)) {
            $ffmpeg .= sprintf(' -http_proxy %s', buildHttpProxyString());
        }

        $command = "$ffmpeg -i $url -c copy $path";
        exec($command, $exOutput, $result);

        // check if command had errors and verify the file
        if ($result != 0 || ! $this->verifyDownloadedFile($path, $audioItem)) {
            return false;
        }

        $elapsed = round(microtime(true) - $startedAt, 3);
        logger()->statsHlsDownloadTime($audioItem['id'], $elapsed);

        return true;
    }

    /**
     * Verify whether the given file is an audio file.
     *
     * @param  string  $path
     * @param  array  $audioItem
     * @return bool
     */
    private function verifyDownloadedFile(string $path, array $audioItem)
    {
        $fileMimeType = get_mime_type($path);
        if (! isMimeTypeAudio($fileMimeType)) {
            logger()->log('Download.Fail.InvalidAudio', json_encode([$audioItem['id'], $audioItem['artist'], $audioItem['title'], $fileMimeType]));
            @unlink($path);

            return false;
        } else {
            return true;
        }
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
                if ($coverImage = covers()->getCoverFile($audio)) {
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
     * Dispatch post process audio job if enabled.
     *
     * @param  array  $audioItem  audio item info
     */
    public function onDownloadCallback(array $audioItem)
    {
        if (config('app.downloading.post_process.enabled')) {
            dispatch(new PostProcessAudioJob($audioItem))->onQueue('post_process');
        }
    }
}
